<script type="text/ecmascript-6">
    import axios from 'axios';

    export default {
        props: ['entry', 'batch'],

        methods: {
            batchEntriesOfType(type) {
                return _.filter(this.batch, {type: type})
            }
        }
    }
</script>

<template>
<div>
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

        <table class="table table-hover table-sm mb-0">
            <thead>
            <tr>
                <th>Message</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="entry in batchEntriesOfType(2)">
                <td>{{truncate(entry.content.message, 80)}}</td>
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

    <div class="card mt-5" v-if="batchEntriesOfType(10).length">
        <div class="card-header"><h5>Exceptions</h5></div>

        <table class="table table-hover table-sm mb-0">
            <thead>
            <tr>
                <th>Message</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="entry in batchEntriesOfType(10)">
                <td>{{truncate(entry.content.message, 80)}}</td>
                <td class="table-fit">
                    <router-link :to="{name:'exception-preview', params:{id: entry.id}}" class="control-action">
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
</template>
