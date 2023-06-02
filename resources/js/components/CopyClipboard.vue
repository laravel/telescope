<script type="text/ecmascript-6">
import CopyToClipboard from 'vue-copy-to-clipboard'

export default {
    props: ['data'],

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
        copyText() {
            if (typeof this.data === "string") {
                return this.data;
            }

            return JSON.stringify(this.data, null, '\t');
        }
    }
}
</script>

<template>
    <div class="position-relative">
        <div class="copy-to-clipboard">
            <span v-if="copying">Copied...</span>
            <copy-to-clipboard v-else :text="copyText" @copy="handleCopy">
                <a href="#">
                    <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                         style="-ms-transform: rotate(360deg); -webkit-transform: rotate(360deg); transform: rotate(360deg);"
                         preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24">
                        <path
                            d="M19 21H8V7h11m0-2H8a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2m-3-4H4a2 2 0 0 0-2 2v14h2V3h12V1z"/>
                    </svg>
                </a>
            </copy-to-clipboard>
        </div>
        <slot></slot>
    </div>

</template>

