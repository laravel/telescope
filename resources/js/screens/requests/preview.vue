<script type="text/ecmascript-6">
    import axios from 'axios';

    export default {
        data(){
            return {
                entry: null,
                batch: [],
            };
        },

        methods: {
            batchEntriesOfType(type){
                return _.filter(this.batch, {type: type})
            }
        }
    }
</script>

<template>
    <preview-screen title="Request Preview" resource="requests" :id="$route.params.id">
        <template slot="table-parameters" slot-scope="slotProps">
        <tr>
            <td class="table-fit font-weight-bold">Path</td>
            <td>
                {{slotProps.entry.content.uri}}
            </td>
        </tr>

        <tr>
            <td class="table-fit font-weight-bold">Method</td>
            <td>
                {{slotProps.entry.content.method}}
            </td>
        </tr>

        <tr>
            <td class="table-fit font-weight-bold">Status</td>
            <td>
                {{slotProps.entry.content.response_status}}
            </td>
        </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps">
            <div class="card mt-5" v-if="slotProps.entry.content.payload">
                <div class="card-header"><h5>Payload</h5></div>

                <pre class="bg-dark p-4 mb-0 text-white">{{slotProps.entry.content.payload}}</pre>
            </div>

            <div class="card mt-5" v-if="slotProps.entry.content.headers">
                <div class="card-header"><h5>Headers</h5></div>

                <pre class="bg-dark p-4 mb-0 text-white">{{slotProps.entry.content.headers}}</pre>
            </div>

            <div class="card mt-5" v-if="slotProps.entry.content.response">
                <div class="card-header"><h5>Response</h5></div>

                <pre class="bg-dark p-4 mb-0 text-white">{{slotProps.entry.content.response}}</pre>
            </div>

            <div class="card mt-5" v-if="batchEntriesOfType(7).length">
                <div class="card-header"><h5>Queries</h5></div>

                <table class="table table-hover table-sm mb-0 penultimate-column-right">
                    <thead>
                    <tr>
                        <th>Query</th>
                        <th>Connection</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="entry in batchEntriesOfType(7)">
                        <td>{{truncate(entry.content.sql, 90)}}</td>
                        <td class="table-fit">{{truncate(entry.content.connection, 20)}}</td>
                        <td class="table-fit">
                            <router-link :to="{name:'query-preview', params:{id: entry.id}}" class="control-action">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 16">
                                    <path d="M16.56 13.66a8 8 0 0 1-11.32 0L.3 8.7a1 1 0 0 1 0-1.42l4.95-4.95a8 8 0 0 1 11.32 0l4.95 4.95a1 1 0 0 1 0 1.42l-4.95 4.95-.01.01zm-9.9-1.42a6 6 0 0 0 8.48 0L19.38 8l-4.24-4.24a6 6 0 0 0-8.48 0L2.4 8l4.25 4.24h.01zM10.9 12a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm0-2a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"></path>
                                </svg>
                            </router-link>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div class="card mt-5" v-if="batchEntriesOfType(2).length">
                <div class="card-header"><h5>Log Entries</h5></div>

                <table class="table table-hover table-sm mb-0 penultimate-column-right">
                    <thead>
                    <tr>
                        <th>Message</th>
                        <th>Level</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="entry in batchEntriesOfType(2)">
                        <td>{{truncate(entry.content.message, 80)}}</td>
                        <td class="table-fit">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon fill-danger" v-if="entry.content.level == 'error'">
                                <path d="M15.3 14.89l2.77 2.77a1 1 0 0 1 0 1.41 1 1 0 0 1-1.41 0l-2.59-2.58A5.99 5.99 0 0 1 11 18V9.04a1 1 0 0 0-2 0V18a5.98 5.98 0 0 1-3.07-1.51l-2.59 2.58a1 1 0 0 1-1.41 0 1 1 0 0 1 0-1.41l2.77-2.77A5.95 5.95 0 0 1 4.07 13H1a1 1 0 1 1 0-2h3V8.41L.93 5.34a1 1 0 0 1 0-1.41 1 1 0 0 1 1.41 0l2.1 2.1h11.12l2.1-2.1a1 1 0 0 1 1.41 0 1 1 0 0 1 0 1.41L16 8.41V11h3a1 1 0 1 1 0 2h-3.07c-.1.67-.32 1.31-.63 1.89zM15 5H5a5 5 0 1 1 10 0z"/>
                            </svg>

                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon fill-info" v-else>
                                <path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zM9 11v4h2V9H9v2zm0-6v2h2V5H9z"/>
                            </svg>
                        </td>
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
            </div>

            <div class="card mt-5" v-if="batchEntriesOfType(6).length">
                <div class="card-header"><h5>Cache</h5></div>

                <table class="table table-hover table-sm mb-0 penultimate-column-right">
                    <thead>
                    <tr>
                        <th>Key</th>
                        <th>Action</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="entry in batchEntriesOfType(6)">
                        <td>{{truncate(entry.content.key, 80)}}</td>
                        <td class="table-fit">{{entry.content.type}}</td>
                        <td class="table-fit">
                            <router-link :to="{name:'cache-preview', params:{id: entry.id}}" class="control-action">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 16">
                                    <path d="M16.56 13.66a8 8 0 0 1-11.32 0L.3 8.7a1 1 0 0 1 0-1.42l4.95-4.95a8 8 0 0 1 11.32 0l4.95 4.95a1 1 0 0 1 0 1.42l-4.95 4.95-.01.01zm-9.9-1.42a6 6 0 0 0 8.48 0L19.38 8l-4.24-4.24a6 6 0 0 0-8.48 0L2.4 8l4.25 4.24h.01zM10.9 12a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm0-2a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"></path>
                                </svg>
                            </router-link>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div class="card mt-5" v-if="batchEntriesOfType(5).length">
                <div class="card-header"><h5>Events</h5></div>

                <table class="table table-hover table-sm mb-0 penultimate-column-right">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Listeners</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="entry in batchEntriesOfType(5)">
                        <td>{{truncate(entry.content.event_name, 80)}}</td>
                        <td class="table-fit">{{entry.content.listeners.length}}</td>
                        <td class="table-fit">
                            <router-link :to="{name:'event-preview', params:{id: entry.id}}" class="control-action">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 16">
                                    <path d="M16.56 13.66a8 8 0 0 1-11.32 0L.3 8.7a1 1 0 0 1 0-1.42l4.95-4.95a8 8 0 0 1 11.32 0l4.95 4.95a1 1 0 0 1 0 1.42l-4.95 4.95-.01.01zm-9.9-1.42a6 6 0 0 0 8.48 0L19.38 8l-4.24-4.24a6 6 0 0 0-8.48 0L2.4 8l4.25 4.24h.01zM10.9 12a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm0-2a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"></path>
                                </svg>
                            </router-link>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </preview-screen>
</template>
