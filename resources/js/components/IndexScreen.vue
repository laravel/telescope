<script type="text/ecmascript-6">
    import $ from 'jquery';
    import _ from 'lodash';
    import axios from 'axios';

    export default {
        props: [
            'resource', 'title', 'showAllFamily', 'hideSearch'
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
                recordingStatus: 'enabled',
                lastEntryIndex: '',
                hasMoreEntries: true,
                hasNewEntries: false,
                entriesPerRequest: 50,
                loadingNewEntries: false,
                loadingMoreEntries: false,

                updateTimeAgoTimeout: null,

                newEntriesTimeout: null,
                newEntriesTimer: 2500,

                updateEntriesTimeout: null,
                updateEntriesTimer: 2500,
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

                this.checkForNewEntries();

                this.ready = true;
            });

            this.updateEntries();

            this.updateTimeAgo();

            this.focusOnSearch();
        },


        /**
         * Clean after the component is destroyed.
         */
        destroyed() {
            clearTimeout(this.newEntriesTimeout);
            clearTimeout(this.updateEntriesTimeout);
            clearTimeout(this.updateTimeAgoTimeout);

            document.onkeyup = null;
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

                    this.checkForNewEntries();

                    this.ready = true;
                });
            },
        },


        methods: {
            loadEntries(after){
                axios.post(Telescope.basePath + '/telescope-api/' + this.resource +
                        '?tag=' + this.tag +
                        '&before=' + this.lastEntryIndex +
                        '&take=' + this.entriesPerRequest +
                        '&family_hash=' + this.familyHash
                ).then(response => {
                    this.lastEntryIndex = response.data.entries.length ? _.last(response.data.entries).sequence : this.lastEntryIndex;

                    this.hasMoreEntries = response.data.entries.length >= this.entriesPerRequest;

                    this.recordingStatus = response.data.status;

                    if (_.isFunction(after)) {
                        after(
                                this.familyHash || this.showAllFamily ? response.data.entries : _.uniqBy(response.data.entries, entry => entry.family_hash || _.uniqueId())
                        );
                    }
                })
            },


            /**
             * Keep checking if there are new entries.
             */
            checkForNewEntries(){
                this.newEntriesTimeout = setTimeout(() => {
                    axios.post(Telescope.basePath + '/telescope-api/' + this.resource +
                            '?tag=' + this.tag +
                            '&take=1' +
                            '&family_hash=' + this.familyHash
                    ).then(response => {
                        if (! this._isDestroyed) {
                            this.recordingStatus = response.data.status;

                            if (response.data.entries.length && !this.entries.length) {
                                this.loadNewEntries();
                            } else if (response.data.entries.length && _.first(response.data.entries).id !== _.first(this.entries).id) {
                                if (this.$root.autoLoadsNewEntries) {
                                    this.loadNewEntries();
                                } else {
                                    this.hasNewEntries = true;
                                }
                            } else {
                                this.checkForNewEntries();
                            }
                        }
                    })
                }, this.newEntriesTimer);
            },


            /**
             * Update the timeago of each entry.
             */
            updateTimeAgo(){
                this.updateTimeAgoTimeout = setTimeout(() => {
                    _.each($('[data-timeago]'), time => {
                        $(time).html(this.timeAgo($(time).data('timeago')));
                    });

                    this.updateTimeAgo();
                }, 60000)
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

                clearTimeout(this.newEntriesTimeout);

                this.loadEntries((entries) => {
                    this.entries = entries;

                    this.loadingNewEntries = false;

                    this.checkForNewEntries();
                });
            },


            /**
             * Update the existing entries if needed.
             */
            updateEntries(){
                if (this.resource !== 'jobs') return;

                this.updateEntriesTimeout = setTimeout(() => {
                    let uuids = _.chain(this.entries).filter(entry => entry.content.status === 'pending').map('id').value();

                    if (uuids.length) {
                        axios.post(Telescope.basePath + '/telescope-api/' + this.resource, {
                            uuids: uuids
                        }).then(response => {
                            this.recordingStatus = response.data.status;

                            this.entries = _.map(this.entries, entry => {
                                if (!_.includes(uuids, entry.id)) return entry;

                                return _.find(response.data.entries, {id: entry.id});
                            });
                        })
                    }

                    this.updateEntries();
                }, this.updateEntriesTimer);
            },


            /**
             * Focus on the search input when "/" key is hit.
             */
            focusOnSearch(){
                document.onkeyup = event => {
                    if (event.which === 191 || event.keyCode === 191) {
                        let searchInput = document.getElementById("searchInput");

                        if (searchInput) {
                            searchInput.focus();
                        }
                    }
                };
            }
        }
    }
</script>

<template>
    <div class="card overflow-hidden">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h2 class="h6 m-0">{{this.title}}</h2>

            <div class="form-control-with-icon w-25" v-if="!hideSearch && (tag || entries.length > 0)">
                <div class="icon-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <input type="text" class="form-control w-100"
                       id="searchInput"
                       placeholder="Search Tag" v-model="tag" @input.stop="search">
            </div>
        </div>

        <p v-if="recordingStatus !== 'enabled'" class="mt-0 mb-0 disabled-watcher d-flex align-items-center">
            <svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="20px" height="20px" viewBox="0 0 90 90" class="mr-2">
                <path fill="#FFFFFF" d="M45 0C20.1 0 0 20.1 0 45s20.1 45 45 45 45-20.1 45-45S69.9 0 45 0zM45 74.5c-3.6 0-6.5-2.9-6.5-6.5s2.9-6.5 6.5-6.5 6.5 2.9 6.5 6.5S48.6 74.5 45 74.5zM52.1 23.9l-2.5 29.6c0 2.5-2.1 4.6-4.6 4.6 -2.5 0-4.6-2.1-4.6-4.6l-2.5-29.6c-0.1-0.4-0.1-0.7-0.1-1.1 0-4 3.2-7.2 7.2-7.2 4 0 7.2 3.2 7.2 7.2C52.2 23.1 52.2 23.5 52.1 23.9z"></path>
            </svg>
            <span class="ml-1" v-if="recordingStatus == 'disabled'">Telescope is currently disabled.</span>
            <span class="ml-1" v-if="recordingStatus == 'paused'">Telescope recording is paused.</span>
            <span class="ml-1" v-if="recordingStatus == 'off'">This watcher is turned off.</span>
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


        <table id="indexScreen" class="table table-hover mb-0 penultimate-column-right" v-if="ready && entries.length > 0">
            <thead>
            <slot name="table-header"></slot>
            </thead>


            <transition-group tag="tbody" name="list">
                <tr v-if="hasNewEntries" key="newEntries" class="dontanimate">
                    <td colspan="100" class="text-center card-bg-secondary py-2">
                        <small><a href="#" v-on:click.prevent="loadNewEntries" v-if="!loadingNewEntries">Load New Entries</a></small>

                        <small v-if="loadingNewEntries">Loading...</small>
                    </td>
                </tr>


                <tr v-for="entry in entries" :key="entry.id">
                    <slot name="row" :entry="entry"></slot>
                </tr>


                <tr v-if="hasMoreEntries" key="olderEntries" class="dontanimate">
                    <td colspan="100" class="text-center card-bg-secondary py-2">
                        <small><a href="#" v-on:click.prevent="loadOlderEntries" v-if="!loadingMoreEntries">Load Older Entries</a></small>

                        <small v-if="loadingMoreEntries">Loading...</small>
                    </td>
                </tr>
            </transition-group>
        </table>

    </div>
</template>
