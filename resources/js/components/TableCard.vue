<script type="text/ecmascript-6">
    import _ from 'lodash';
    import axios from 'axios';

    export default {
        props: [
            'resource', 'title'
        ],

        components: {},


        /**
         * The component's data.
         */
        data() {
            return {
                entries: [],
                ready: false,
                loadingMoreEntries: false,
                tag: '',
                lastEntryIndex: null,
            };
        },


        /**
         * Prepare the component.
         */
        mounted() {
            document.title = this.title + " - Telescope";

            this.loadEntries((response) => {
                this.entries = response.data.entries;

                this.ready = true;
            });
        },

        methods: {
            loadEntries(after){
                axios.get('/telescope/telescope-api/' + this.resource + '?tag=' + this.tag + '&before=' + this.lastEntryIndex).then(response => {
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
                    this.lastEntryIndex = null;

                    this.loadEntries((response) => {
                        this.entries = response.data.entries;
                    });
                });
            },


            /**
             * Load more entries.
             */
            loadOlderEntries(){
                this.loadingMoreEntries = true;

                this.loadEntries((response) => {
                    this.entries.push(...response.data.entries);

                    this.loadingMoreEntries = false;
                });
            },
        }
    }
</script>

<template>
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5>{{this.title}}</h5>
            <input type="text" class="form-control w-25"
                   v-if="tag || entries.length > 0"
                   placeholder="Search Tags" v-model="tag" @input.stop="search">
        </div>


        <div v-if="!ready" class="d-flex align-items-center justify-content-center bg-secondary p-5 bottom-radius">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin mr-2">
                <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"/>
            </svg>

            <span>Scanning...</span>
        </div>


        <div v-if="ready && entries.length == 0" class="d-flex align-items-center justify-content-center bg-secondary p-5 bottom-radius">
            <span>No entries found</span>
        </div>


        <table v-if="ready && entries.length > 0" class="table table-hover mb-0 penultimate-column-right">
            <thead>
            <slot name="table-header"></slot>
            </thead>


            <tbody>
            <slot name="row" v-for="entry in entries" :entry="entry"></slot>
            </tbody>
        </table>


        <div class="d-flex align-items-center justify-content-center bg-secondary p-1 border-top paginator bottom-radius" v-if="entries.length">
            <button class="btn btn-link" v-on:click.prevent="loadOlderEntries" v-if="!loadingMoreEntries">Load Older Entries</button>

            <div v-if="loadingMoreEntries" class="p-2">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin mr-2">
                    <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"/>
                </svg>

                <span>Scanning...</span>
            </div>
        </div>
    </div>
</template>