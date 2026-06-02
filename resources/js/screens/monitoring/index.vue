<script type="text/ecmascript-6">
import $ from 'jquery';
import _ from 'lodash';
import axios from 'axios';

export default {
    /**
     * The component's data.
     */
    data() {
        return {
            tags: [],
            endpoints: [],
            ready: false,
            newTag: '',
            newEndpoint: '',
        };
    },

    /**
     * Prepare the component.
     */
    mounted(){
        document.title = "Monitoring - Telescope";

        Promise.all([
            axios.get(Telescope.basePath + '/telescope-api/monitored-tags'),
            axios.get(Telescope.basePath + '/telescope-api/monitored-endpoints'),
        ]).then(([tagsResponse, endpointsResponse]) => {
            this.tags = tagsResponse.data.tags;
            this.endpoints = endpointsResponse.data.endpoints;

            this.ready = true;
        });
    },


    methods: {
        removeTag(tag){
            this.alertConfirm('Are you sure you want to remove this tag?', ()=> {
                this.tags = _.reject(this.tags, t => t === tag);

                axios.post(Telescope.basePath + '/telescope-api/monitored-tags/delete', {tag: tag});
            });
        },

        removeEndpoint(endpoint){
            this.alertConfirm('Are you sure you want to remove this endpoint?', ()=> {
                this.endpoints = _.reject(this.endpoints, e => e === endpoint);

                axios.post(Telescope.basePath + '/telescope-api/monitored-endpoints/delete', {endpoint: endpoint});
            });
        },


        /**
         * Opens the modal for adding new monitored tag.
         */
        openNewTagModal(){
            $('#addTagModel').modal({
                backdrop: 'static',
            });

            $('#newTagInput').focus();
        },

        /**
         * Opens the modal for adding new monitored endpoint.
         */
        openNewEndpointModal(){
            $('#addEndpointModel').modal({
                backdrop: 'static',
            });

            $('#newEndpointInput').focus();
        },


        /**
         * Monitor the given tag.
         */
        monitorNewTag(){
            if (this.newTag.length) {
                axios.post(Telescope.basePath + '/telescope-api/monitored-tags', {tag: this.newTag});

                this.tags.push(this.newTag);
            }

            $('#addTagModel').modal('hide');

            this.newTag = '';
        },

        /**
         * Monitor the given endpoint.
         */
        monitorNewEndpoint(){
            if (this.newEndpoint.length) {
                axios.post(Telescope.basePath + '/telescope-api/monitored-endpoints', {endpoint: this.newEndpoint});

                this.endpoints.push(this.newEndpoint);
            }

            $('#addEndpointModel').modal('hide');

            this.newEndpoint = '';
        },


        /**
         * Cancel adding a new tag.
         */
        cancelNewTag(){
            $('#addTagModel').modal('hide');

            this.newTag = '';
        },

        /**
         * Cancel adding a new endpoint.
         */
        cancelNewEndpoint(){
            $('#addEndpointModel').modal('hide');

            this.newEndpoint = '';
        },

        /**
         * Navigate to requests filtered by the given endpoint.
         */
        filterByEndpoint(endpoint){
            this.$router.push({
                path: '/requests',
                query: { endpoint: endpoint },
            });
        },
    }
}
</script>

<template>
    <div>
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0">Monitored Tags</h2>

                <button class="btn btn-primary" v-on:click.prevent="openNewTagModal">Monitor Tag</button>
            </div>

            <div v-if="!ready" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin mr-2 fill-text-color">
                    <path
                        d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"
                    ></path>
                </svg>

                <span>Scanning...</span>
            </div>

            <div
                v-if="ready && tags.length == 0"
                class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius"
            >
                <span>No tags are currently being monitored.</span>
            </div>

            <table v-if="ready && tags.length > 0" class="table table-hover mb-0">
                <thead>
                    <th>Tag</th>
                    <th></th>
                </thead>

                <tbody>
                    <tr v-for="tag in tags" :key="tag">
                        <td>{{ truncate(tag, 140) }}</td>

                        <td class="table-fit">
                            <a href="#" class="control-action" v-on:click.prevent="removeTag(tag)">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path
                                        d="M6 2l2-2h4l2 2h4v2H2V2h4zM3 6h14l-1 14H4L3 6zm5 2v10h1V8H8zm3 0v10h1V8h-1z"
                                    ></path>
                                </svg>
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0">Monitored Endpoints</h2>

                <button class="btn btn-primary" v-on:click.prevent="openNewEndpointModal">Monitor Endpoint</button>
            </div>

            <div
                v-if="ready && endpoints.length == 0"
                class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius"
            >
                <span>No endpoints are currently being monitored.</span>
            </div>

            <table v-if="ready && endpoints.length > 0" class="table table-hover mb-0">
                <thead>
                    <th>Endpoint</th>
                    <th></th>
                </thead>

                <tbody>
                    <tr v-for="endpoint in endpoints" :key="endpoint">
                        <td>
                            <a href="#" v-on:click.prevent="filterByEndpoint(endpoint)">{{ truncate(endpoint, 140) }}</a>
                        </td>

                        <td class="table-fit">
                            <a href="#" class="control-action" v-on:click.prevent="removeEndpoint(endpoint)">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path
                                        d="M6 2l2-2h4l2 2h4v2H2V2h4zM3 6h14l-1 14H4L3 6zm5 2v10h1V8H8zm3 0v10h1V8h-1z"
                                    ></path>
                                </svg>
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            class="modal"
            id="addTagModel"
            tabindex="-1"
            role="dialog"
            aria-labelledby="alertModalLabel"
            aria-hidden="true"
        >
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">Monitor New Tag</div>

                    <div class="modal-body">
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Project:6352"
                            v-on:keyup.enter="monitorNewTag"
                            v-model="newTag"
                            id="newTagInput"
                        />
                    </div>

                    <div class="modal-footer justify-content-start flex-row-reverse">
                        <button class="btn btn-primary" @click="monitorNewTag">Monitor</button>

                        <button class="btn" @click="cancelNewTag">Cancel</button>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="modal"
            id="addEndpointModel"
            tabindex="-1"
            role="dialog"
            aria-labelledby="alertModalLabel"
            aria-hidden="true"
        >
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">Monitor New Endpoint</div>

                    <div class="modal-body">
                        <input
                            type="text"
                            class="form-control"
                            placeholder="GET:api/users/*"
                            v-on:keyup.enter="monitorNewEndpoint"
                            v-model="newEndpoint"
                            id="newEndpointInput"
                        />
                        <small class="text-muted d-block mt-2">
                            Use wildcards (api/users/*) or prefix with HTTP method (POST:api/orders).
                        </small>
                    </div>

                    <div class="modal-footer justify-content-start flex-row-reverse">
                        <button class="btn btn-primary" @click="monitorNewEndpoint">Monitor</button>

                        <button class="btn" @click="cancelNewEndpoint">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
