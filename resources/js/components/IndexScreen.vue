<script type="text/ecmascript-6">
    import $ from 'jquery';
    import _ from 'lodash';
    import axios from 'axios';

    export default {
        props: [
            'resource', 'title'
        ],


        /**
         * The component's data.
         */
        data() {
            return {
                tag: '',
                familyHash: '',
                entries: [],
                ready: false,
                lastEntryIndex: '',
                hasMoreEntries: true,
                hasNewEntries: false,
                entriesPerRequest: 50,
                newEntriesTimeout: null,
                loadingNewEntries: false,
                loadingMoreEntries: false,
                newEntriesTimeoutInSeconds: 5000,
            };
        },


        /**
         * Prepare the component.
         */
        mounted() {
            document.title = this.title + " - Telescope";

            this.familyHash = this.$route.query.family_hash || '';

            this.tag = this.$route.query.tag || '';

            this.loadEntries((entries) => {
                this.entries = entries;

                this.newEntriesTimeout = setTimeout(() => {
                    this.checkForNewEntries();
                }, this.newEntriesTimeoutInSeconds);

                this.ready = true;
            });
        },


        /**
         * Clean after the component is destroyed.
         */
        destroyed() {
            clearTimeout(this.newEntriesTimeout);
        },


        watch: {
            '$route.query': function () {
                clearTimeout(this.newEntriesTimeout);

                this.hasNewEntries = false;

                this.lastEntryIndex = '';

                if (!this.$route.query.family_hash) {
                    this.familyHash = '';
                }

                if (!this.$route.query.tag) {
                    this.tag = '';
                }

                this.ready = false;

                this.loadEntries((entries) => {
                    this.entries = entries;

                    this.newEntriesTimeout = setTimeout(() => {
                        this.checkForNewEntries();
                    }, this.newEntriesTimeoutInSeconds);

                    this.ready = true;
                });
            },
        },


        methods: {
            loadEntries(after){
                axios.get('/telescope/telescope-api/' + this.resource + '?tag=' + this.tag + '&before=' + this.lastEntryIndex + '&take=' + this.entriesPerRequest + '&family_hash=' + this.familyHash).then(response => {
                    if (response.data.entries.length) {
                        this.lastEntryIndex = _.last(response.data.entries).sequence;
                    }

                    if (response.data.entries.length < this.entriesPerRequest) {
                        this.hasMoreEntries = false;
                    } else {
                        this.hasMoreEntries = true;
                    }

                    if (_.isFunction(after)) {
                        after(_.uniqBy(response.data.entries, entry => {
                            return entry.family_hash || _.uniqueId();
                        }));
                    }
                })
            },


            /**
             * Keep checking if there are new entries.
             */
            checkForNewEntries(){
                axios.get('/telescope/telescope-api/' + this.resource + '?tag=' + this.tag + '&take=1' + '&family_hash=' + this.familyHash)
                        .then(response => {
                            if (response.data.entries.length && !this.entries.length) {
                                this.loadNewEntries();
                            } else if (response.data.entries.length && _.first(response.data.entries).id != _.first(this.entries).id) {
                                this.hasNewEntries = true;
                            } else {
                                this.newEntriesTimeout = setTimeout(() => {
                                    this.checkForNewEntries();
                                }, this.newEntriesTimeoutInSeconds);
                            }
                        })
            },


            /**
             * Search the entries of this type.
             */
            search(){
                this.debouncer(() => {
                    this.hasNewEntries = false;
                    this.lastEntryIndex = '';

                    clearTimeout(this.newEntriesTimeout);

                    this.$router.push({query: _.assign({}, this.$route.query, {tag: this.tag})});

                    this.loadEntries((entries) => {
                        this.entries = entries;

                        this.newEntriesTimeout = setTimeout(() => {
                            this.checkForNewEntries();
                        }, this.newEntriesTimeoutInSeconds);
                    });
                });
            },


            /**
             * Load more entries.
             */
            loadOlderEntries(){
                this.loadingMoreEntries = true;

                this.loadEntries((entries) => {
                    this.entries.push(...entries);

                    this.loadingMoreEntries = false;
                });
            },


            /**
             * Load new entries.
             */
            loadNewEntries(){
                this.hasMoreEntries = true;
                this.hasNewEntries = false;
                this.lastEntryIndex = '';
                this.loadingNewEntries = true;

                setTimeout(() => {
                    $('.newItem').removeClass('newItem');
                }, 2000);

                clearTimeout(this.newEntriesTimeout);

                this.loadEntries((entries) => {
                    this.entries = entries;

                    this.loadingNewEntries = false;

                    this.newEntriesTimeout = setTimeout(() => {
                        this.checkForNewEntries();
                    }, this.newEntriesTimeoutInSeconds);
                });
            }
        }
    }
</script>

<template>
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5>{{this.title}}</h5>

            <input type="text" class="form-control w-25"
                   v-if="tag || entries.length > 0"
                   placeholder="Search Tag" v-model="tag" @input.stop="search">
        </div>


        <div v-if="!ready" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin mr-2 fill-text-color">
                <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"/>
            </svg>

            <span>Scanning...</span>
        </div>


        <div v-if="ready && entries.length == 0" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
            <span>No entries found.</span>
        </div>


        <table v-if="ready && entries.length > 0" id="indexScreen" class="table table-hover table-sm mb-0 penultimate-column-right">
            <thead>
            <slot name="table-header"></slot>
            </thead>


            <transition-group tag="tbody" name="list">
                <tr v-if="hasNewEntries" key="newEntries" class="dontanimate">
                    <td colspan="100" class="text-center card-bg-secondary py-1">
                        <small><a href="#" v-on:click.prevent="loadNewEntries" v-if="!loadingNewEntries">Load New Entries</a></small>

                        <small v-if="loadingNewEntries">Loading...</small>
                    </td>
                </tr>


                <tr v-for="entry in entries" :key="entry.id">
                    <slot name="row" :entry="entry"></slot>
                </tr>


                <tr v-if="hasMoreEntries" key="olderEntries" class="dontanimate">
                    <td colspan="100" class="text-center card-bg-secondary py-1">
                        <small><a href="#" v-on:click.prevent="loadOlderEntries" v-if="!loadingMoreEntries">Load Older Entries</a></small>

                        <small v-if="loadingMoreEntries">Loading...</small>
                    </td>
                </tr>
            </transition-group>
        </table>
    </div>
</template>