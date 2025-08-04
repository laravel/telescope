<script type="text/ecmascript-6">
import CopyToClipboard from 'vue-copy-to-clipboard'

export default {
    props: ['entry'],

    components: {
        CopyToClipboard
    },

    data() {
        return {
            copying: false,
        }
    },

    methods: {
        async handleCopy() {
            this.copying = true;
            setTimeout(() => this.copying = false, 1000);
        }
    },

    computed: {
        curlCommand() {
            if (!this.entry || !this.entry.content) {
                return '';
            }

            const content = this.entry.content;
            let curl = `curl -X ${content.method}`;

            // Add URL - construct full URL with protocol and host
            const protocol = content.headers && content.headers['x-forwarded-proto'] ? content.headers['x-forwarded-proto'] : 'http';
            const host = content.headers && content.headers.host ? content.headers.host : 'localhost';
            const fullUrl = `${protocol}://${host}${content.uri}`;
            curl += ` '${fullUrl}'`;

            // Add headers
            if (content.headers) {
                for (const [key, value] of Object.entries(content.headers)) {
                    // Skip some headers that shouldn't be included in curl
                    if (!['host', 'content-length', 'connection', 'cache-control'].includes(key.toLowerCase())) {
                        curl += ` \\\n  -H '${key}: ${value}'`;
                    }
                }
            }

            // Add request body for POST, PUT, PATCH requests
            if (['POST', 'PUT', 'PATCH'].includes(content.method) && content.payload) {
                // Handle different payload types
                if (typeof content.payload === 'object') {
                    curl += ` \\\n  -d '${JSON.stringify(content.payload)}'`;
                } else {
                    curl += ` \\\n  -d '${content.payload}'`;
                }
            }

            return curl;
        }
    }
}
</script>

<template>
    <div class="d-inline-block">
        <span v-if="copying" class="btn btn-outline-info btn-sm">
            <svg fill="currentColor" viewBox="0 0 20 20" style="width: 1rem; height: 1rem" class="mr-1">
                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                <path
                    fill-rule="evenodd"
                    d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm9.707 5.707a1 1 0 00-1.414-1.414L9 12.586l-1.293-1.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd"
                ></path>
            </svg>
            Copied!
        </span>

        <copy-to-clipboard v-else :text="curlCommand" @copy="handleCopy">
            <button class="btn btn-outline-info btn-sm">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                    style="width: 1rem; height: 1rem"
                    class="mr-1"
                >
                    <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z"></path>
                    <path
                        d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z"
                    ></path>
                </svg>
                Copy as cURL
            </button>
        </copy-to-clipboard>
    </div>
</template>