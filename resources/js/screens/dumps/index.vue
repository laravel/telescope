<script type="text/ecmascript-6">
    import axios from 'axios';

    export default {
        /**
         * The component's data.
         */
        data() {
            return {
                entries: [],
                ready: false,
                newEntriesTimeout: null,
                newEntriesTimeoutInSeconds: 5000,
            };
        },


        /**
         * Prepare the component.
         */
        mounted() {
            document.title = "Dumps - Telescope";

            this.loadEntries();

        },


        /**
         * Clean after the component is destroyed.
         */
        destroyed() {
            clearTimeout(this.newEntriesTimeout);
        },


        methods: {
            loadEntries(){
                axios.get('/telescope/telescope-api/dumps').then(response => {
                    this.entries = response.data.entries;

                    this.ready = true;

                    this.newEntriesTimeout = setTimeout(() => {
                        this.loadEntries();
                    }, this.newEntriesTimeoutInSeconds);
                });
            }
        }
    }
</script>

<template>
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5>Dumps</h5>
        </div>

        <div v-if="!ready" class="d-flex align-items-center justify-content-center bg-secondary p-5 bottom-radius">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin mr-2 fill-text-color">
                <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"/>
            </svg>

            <span>Scanning...</span>
        </div>


        <div v-if="ready && entries.length == 0" class="d-flex align-items-center justify-content-center bg-secondary p-5 bottom-radius">
            <span>No entries found.</span>
        </div>

        <div v-if="ready && entries.length > 0" class="bg-dark px-3 pt-3">
            <div v-for="entry in entries" :key="entry.id" class="mb-4">
                <div class="entryPointDescription" v-if="entry.content.entry_point_type == 'request'">
                    <router-link :to="{name:'request-preview', params:{id: entry.content.entry_point_uuid}}" class="control-action">
                        Request: {{entry.content.entry_point_description}}
                    </router-link>
                </div>
                <div class="entryPointDescription" v-if="entry.content.entry_point_type == 'job'">
                    <router-link :to="{name:'job-preview', params:{id: entry.content.entry_point_uuid}}" class="control-action">
                        Job: {{entry.content.entry_point_description}}
                    </router-link>
                </div>
                <div class="entryPointDescription" v-if="entry.content.entry_point_type == 'command'">
                    <router-link :to="{name:'command-preview', params:{id: entry.content.entry_point_uuid}}" class="control-action">
                        Command: {{entry.content.entry_point_description}}
                    </router-link>
                </div>


                <div v-html="entry.content.dump"></div>
            </div>
        </div>
    </div>
</template>

<style>
    pre.sf-dump, pre.sf-dump .sf-dump-default {
        background: none !important;
    }

    pre.sf-dump{
        padding-left: 0 !important;
    }

    .entryPointDescription{
        background: black;
        padding-left: 5px;
    }

    .entryPointDescription a{
        font: 12px Menlo, Monaco, Consolas, monospace;
        color: white;
        text-decoration: underline;
    }
</style>