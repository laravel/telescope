<script type="text/ecmascript-6">
import _ from 'lodash';
import axios from 'axios';

export default {
    props: {
        resource: {required: true},
        title: {required: true},
        id: {required: true},
        entryPoint: {default: false},
    },


    /**
     * The component's data.
     */
    data() {
        return {
            entry: null,
            batch: null,
            ready: false,

            updateEntryTimeout: null,
            updateEntryTimer: 2500,
        };
    },


    watch: {
        id() {
            this.prepareEntry()
        }
    },


    /**
     * Prepare the component.
     */
    mounted() {
        this.prepareEntry()
    },


    /**
     * Clean after the component is destroyed.
     */
    destroyed() {
        clearTimeout(this.updateEntryTimeout);
    },


    computed: {
        job() {
            return _.find(this.batch, {type: 'job'})
        },

        request() {
            return _.find(this.batch, {type: 'request'})
        },


        command() {
            return _.find(this.batch, {type: 'command'})
        },
    },


    methods: {
        prepareEntry() {
            document.title = this.title + " - Telescope";
            this.ready = false;

            let unwatch = this.$watch('ready', newVal => {
                if (newVal) {
                    this.$emit('ready');
                    unwatch();
                }
            });

            this.loadEntry((response) => {
                this.entry = response.data.entry;
                this.batch = response.data.batch;

                this.$parent.entry = response.data.entry;
                this.$parent.batch = response.data.batch;

                this.ready = true;

                this.updateEntry();
            });
        },


        loadEntry(after){
            axios.get(Telescope.basePath + '/telescope-api/' + this.resource + '/' + this.id).then(response => {
                if (_.isFunction(after)) {
                    after(response);
                }
            }).catch(error => {
                this.ready = true;
            })
        },


        /**
         * Update the existing entry if needed.
         */
        updateEntry(){
            if (this.resource != 'jobs') return;
            if (this.entry.content.status !== 'pending') return;

            this.updateEntryTimeout = setTimeout(() => {
                this.loadEntry((response) => {
                    this.entry = response.data.entry;
                    this.batch = response.data.batch;

                    this.$parent.entry = response.data.entry;
                    this.$parent.batch = response.data.batch;

                    this.ready = true;
                });

                this.updateEntry();
            }, this.updateEntryTimer);
        }
    }
}
</script>

<template>
    <div>
        <div class="card overflow-hidden">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0">{{ this.title }}</h2>
            </div>

            <div
                v-if="!ready"
                class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin mr-2">
                    <path
                        d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"
                    ></path>
                </svg>

                <span>Fetching...</span>
            </div>

            <div
                v-if="ready && !entry"
                class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius"
            >
                <span>No entry found.</span>
            </div>

            <div class="table-responsive border-top">
                <table v-if="ready && entry" class="table mb-0 card-bg-secondary table-borderless">
                    <tbody>
                        <tr>
                            <td class="table-fit text-muted">Time</td>
                            <td>{{ localTime(entry.created_at) }} ({{ timeAgo(entry.created_at) }})</td>
                        </tr>

                        <tr>
                            <td class="table-fit text-muted">Hostname</td>
                            <td>
                                {{ entry.content.hostname }}
                            </td>
                        </tr>

                        <slot name="table-parameters" :entry="entry"></slot>

                        <tr v-if="!entryPoint && job">
                            <td class="table-fit text-muted">Job</td>
                            <td>
                                <router-link
                                    :to="{
                                        name: 'job-preview',
                                        params: { id: job.id },
                                    }"
                                    class="control-action"
                                >
                                    View Job
                                </router-link>
                            </td>
                        </tr>

                        <tr v-if="!entryPoint && request">
                            <td class="table-fit text-muted">Request</td>
                            <td>
                                <router-link
                                    :to="{
                                        name: 'request-preview',
                                        params: { id: request.id },
                                    }"
                                    class="control-action"
                                >
                                    View Request
                                </router-link>
                            </td>
                        </tr>

                        <tr v-if="!entryPoint && command">
                            <td class="table-fit text-muted">Command</td>
                            <td>
                                <router-link
                                    :to="{
                                        name: 'command-preview',
                                        params: { id: command.id },
                                    }"
                                    class="control-action"
                                >
                                    View Command
                                </router-link>
                            </td>
                        </tr>

                        <tr v-if="entry.tags.length">
                            <td class="table-fit text-muted">Tags</td>
                            <td>
                                <router-link
                                    v-for="tag in entry.tags"
                                    :key="tag"
                                    :to="{
                                        name: resource,
                                        query: { tag: tag },
                                    }"
                                    class="badge badge-info mr-1"
                                >
                                    {{ tag }}
                                </router-link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <slot v-if="ready && entry" name="below-table" :entry="entry"></slot>
        </div>

        <!-- User Information -->
        <div class="card mt-5" v-if="ready && entry && entry.content.user && entry.content.user.id">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5>Authenticated User</h5>
            </div>

            <table class="table mb-0 card-bg-secondary table-borderless">
                <tr>
                    <td class="table-fit text-muted">ID</td>

                    <td>
                        {{ entry.content.user.id }}
                    </td>
                </tr>

                <tr v-if="entry.content.user.name">
                    <td class="table-fit text-muted align-middle">Name</td>

                    <td class="align-middle">
                        <img
                            :src="entry.content.user.avatar"
                            :alt="entry.content.user.name"
                            class="mr-2 rounded-circle"
                            height="40"
                            width="40"
                            v-if="entry.content.user.avatar"
                        />
                        {{ entry.content.user.name }}
                    </td>
                </tr>

                <tr v-if="entry.content.user.email">
                    <td class="table-fit text-muted">Email Address</td>

                    <td>
                        {{ entry.content.user.email }}
                    </td>
                </tr>
            </table>
        </div>

        <slot v-if="ready && entry" name="after-attributes-card" :entry="entry"></slot>
    </div>
</template>
