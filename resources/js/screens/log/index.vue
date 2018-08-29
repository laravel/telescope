<script type="text/ecmascript-6">
    import _ from 'lodash';
    import axios from 'axios';

    export default {
        components: {},


        /**
         * The component's data.
         */
        data() {
            return {
                entries: [],
                ready: false,
                tag: '',
                lastEntryIndex: null,
            };
        },


        /**
         * Prepare the component.
         */
        mounted() {
            document.title = "Log Entries - Telescope";

            this.loadEntries((response) => {
                this.entries = response.data.entries;

                this.ready = true;
            });
        },

        methods: {
            loadEntries(after){
                axios.get('/telescope/telescope-api/log?tag=' + this.tag + '&before=' + this.lastEntryIndex).then(response => {
                    this.lastEntryIndex = _.last(this.entries) ? _.last(this.entries).id : null;

                    if (_.isFunction(after)) {
                        after(response);
                    }
                })
            },


            /**
             * Search the entries of this type.
             */
            search(){
                this.debouncer(() => {
                    this.loadEntries((response) => {
                        this.entries = response.data.entries;
                    });
                });
            },


            /**
             * Load more entries.
             */
            loadOlderEntries(){
                this.loadEntries((response) => {
                    this.entries = response.data.entries;
                });
            },
        }
    }
</script>

<template>
    <loader :loading="!ready">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5>Log Entries</h5>
                <input type="text" class="form-control w-25" placeholder="Search Tags" v-model="tag" @input.stop="search">
            </div>
            <table class="table mb-0">
                <thead>
                <tr>
                    <th scope="col">Message</th>
                    <th scope="col">Type</th>
                    <th scope="col">Since</th>
                    <th scope="col"></th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="entry in entries">
                    <td>{{truncate(entry.content.message, 80)}}</td>
                    <td class="table-fit">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon fill-danger" v-if="entry.content.level == 'error'">
                            <path d="M15.3 14.89l2.77 2.77a1 1 0 0 1 0 1.41 1 1 0 0 1-1.41 0l-2.59-2.58A5.99 5.99 0 0 1 11 18V9.04a1 1 0 0 0-2 0V18a5.98 5.98 0 0 1-3.07-1.51l-2.59 2.58a1 1 0 0 1-1.41 0 1 1 0 0 1 0-1.41l2.77-2.77A5.95 5.95 0 0 1 4.07 13H1a1 1 0 1 1 0-2h3V8.41L.93 5.34a1 1 0 0 1 0-1.41 1 1 0 0 1 1.41 0l2.1 2.1h11.12l2.1-2.1a1 1 0 0 1 1.41 0 1 1 0 0 1 0 1.41L16 8.41V11h3a1 1 0 1 1 0 2h-3.07c-.1.67-.32 1.31-.63 1.89zM15 5H5a5 5 0 1 1 10 0z"/>
                        </svg>

                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon fill-info" v-else>
                            <path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zM9 11v4h2V9H9v2zm0-6v2h2V5H9z"/>
                        </svg>
                    </td>
                    <td class="table-fit">{{timeAgo(entry.created_at)}}</td>
                    <td class="table-fit">
                        <router-link :to="{name:'log-preview', params:{id: entry.id}}" class="control-action">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 16">
                                <path d="M16.56 13.66a8 8 0 0 1-11.32 0L.3 8.7a1 1 0 0 1 0-1.42l4.95-4.95a8 8 0 0 1 11.32 0l4.95 4.95a1 1 0 0 1 0 1.42l-4.95 4.95-.01.01zm-9.9-1.42a6 6 0 0 0 8.48 0L19.38 8l-4.24-4.24a6 6 0 0 0-8.48 0L2.4 8l4.25 4.24h.01zM10.9 12a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm0-2a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"></path>
                            </svg>
                        </router-link>
                    </td>
                </tr>
                </tbody>
            </table>
            <div class="d-flex align-items-center justify-content-center bg-secondary p-1 border-top paginator">
                <button class="btn btn-link" v-on:click.prevent="loadOlderEntries">Load Older Entries</button>
            </div>
        </div>
    </loader>
</template>
