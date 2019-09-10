<script type="text/ecmascript-6">
    import axios from 'axios';

    export default {
        /**
         * The component's data.
         */
        data() {
            return {
                dump: null,
                entries: [],
                ready: false,
                newEntriesTimeout: null,
                newEntriesTimer: 2000,
                recordingStatus: 'enabled'
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
                axios.post(Telescope.basePath + '/telescope-api/dumps').then(response => {
                    this.ready = true;
                    this.dump = response.data.dump;
                    this.entries = response.data.entries;
                    this.recordingStatus = response.data.status;

                    this.checkForNewEntries();
                });
            },


            /**
             * Keep checking if there are new entries.
             */
            checkForNewEntries(){
                this.newEntriesTimeout = setTimeout(() => {
                    axios.post(Telescope.basePath + '/telescope-api/dumps?take=1').then(response => {
                        this.recordingStatus = response.data.status;

                        if (response.data.entries.length && !this.entries.length) {
                            this.loadEntries();
                        } else if (response.data.entries.length && _.first(response.data.entries).id !== _.first(this.entries).id) {
                            this.loadEntries();
                        } else {
                            this.checkForNewEntries();
                        }
                    })
                }, this.newEntriesTimer);
            },
        }
    }
</script>

<template>
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5>Dumps</h5>
        </div>

        <p v-if="recordingStatus !== 'enabled'" class="mt-0 mb-0 disabled-watcher">
            <svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="20px" height="20px" viewBox="0 0 90 90">
                <path fill="#FFFFFF" d="M45 0C20.1 0 0 20.1 0 45s20.1 45 45 45 45-20.1 45-45S69.9 0 45 0zM45 74.5c-3.6 0-6.5-2.9-6.5-6.5s2.9-6.5 6.5-6.5 6.5 2.9 6.5 6.5S48.6 74.5 45 74.5zM52.1 23.9l-2.5 29.6c0 2.5-2.1 4.6-4.6 4.6 -2.5 0-4.6-2.1-4.6-4.6l-2.5-29.6c-0.1-0.4-0.1-0.7-0.1-1.1 0-4 3.2-7.2 7.2-7.2 4 0 7.2 3.2 7.2 7.2C52.2 23.1 52.2 23.5 52.1 23.9z"></path>
            </svg>
            <span class="ml-1" v-if="recordingStatus == 'disabled'">Telescope is currently disabled.</span>
            <span class="ml-1" v-if="recordingStatus == 'paused'">Telescope recording is paused.</span>
            <span class="ml-1" v-if="recordingStatus == 'off'">This watcher is turned off.</span>
            <span class="ml-1" v-if="recordingStatus == 'wrong-cache'">The 'array' cache cannot be used. Please use a persistent cache.</span>
        </p>

        <div v-if="!ready" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin mr-2 fill-text-color">
                <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
            </svg>

            <span>Scanning...</span>
        </div>


        <div v-if="ready && entries.length == 0" class="d-flex flex-column align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 60" class="fill-text-color" style="width: 200px;">
                <path fill-rule="evenodd" d="M7 10h41a11 11 0 0 1 0 22h-8a3 3 0 0 0 0 6h6a6 6 0 1 1 0 12H10a4 4 0 1 1 0-8h2a2 2 0 1 0 0-4H7a5 5 0 0 1 0-10h3a3 3 0 0 0 0-6H7a6 6 0 1 1 0-12zm14 19a1 1 0 0 1-1-1 1 1 0 0 0-2 0 1 1 0 0 1-1 1 1 1 0 0 0 0 2 1 1 0 0 1 1 1 1 1 0 0 0 2 0 1 1 0 0 1 1-1 1 1 0 0 0 0-2zm-5.5-11a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zm24 10a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zm1 18a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zm-14-3a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zm22-23a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zM33 18a1 1 0 0 1-1-1v-1a1 1 0 0 0-2 0v1a1 1 0 0 1-1 1h-1a1 1 0 0 0 0 2h1a1 1 0 0 1 1 1v1a1 1 0 0 0 2 0v-1a1 1 0 0 1 1-1h1a1 1 0 0 0 0-2h-1z"></path>
            </svg>

            <span>We didn't find anything - just empty space.</span>
        </div>

        <div style="display: none;" v-if="dump">
            <div v-html="dump"></div>
        </div>

        <div v-if="ready && entries.length > 0" class="code-bg px-3 pt-3">
            <transition-group tag="div" name="list">
                
                <div v-for="entry in entries" :key="entry.id" class="mb-4">
                    <div class="entryPointDescription d-flex justify-content-between align-items-center">
                        <router-link :to="{name:'request-preview', params:{id: entry.content.entry_point_uuid}}" class="control-action" v-if="entry.content.entry_point_type == 'request'">
                            Request: {{entry.content.entry_point_description}}
                        </router-link>
                        <router-link :to="{name:'job-preview', params:{id: entry.content.entry_point_uuid}}" class="control-action" v-if="entry.content.entry_point_type == 'job'">
                            Job: {{entry.content.entry_point_description}}
                        </router-link>
                        <router-link :to="{name:'command-preview', params:{id: entry.content.entry_point_uuid}}" class="control-action" v-if="entry.content.entry_point_type == 'command'">
                            Command: {{entry.content.entry_point_description}}
                        </router-link>

                        <span class="text-white text-monospace" style="font-size: 12px;">{{timeAgo(entry.created_at)}}</span>
                    </div>

                    <div v-html="entry.content.dump"></div>
                </div>
            </transition-group>
        </div>
    </div>
</template>

<style>
    pre.sf-dump, pre.sf-dump .sf-dump-default {
        background: none !important;
    }

    pre.sf-dump {
        padding-left: 0 !important;
    }

    .entryPointDescription {
        background: black;
        padding-left: 5px;
        padding-right: 5px;
    }

    .entryPointDescription a {
        font: 12px Menlo, Monaco, Consolas, monospace;
        color: white;
        text-decoration: underline;
    }
</style>
