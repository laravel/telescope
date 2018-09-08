<script type="text/ecmascript-6">
    export default {
        props: ['entry', 'batch'],

        methods: {
            batchEntriesOfType(type) {
                return _.filter(this.batch, { type: type })
            }
        },

        computed: {
            queryEntries() {
                return this.batchEntriesOfType('query')
            }
        }
    }
</script>

<template>
<div>
    <!-- Exceptions -->
    <div class="card mt-5" v-if="batchEntriesOfType('exception').length">
        <div class="card-header"><h5>Exceptions</h5></div>

        <table class="table table-hover table-sm mb-0">
            <thead>
                <tr>
                    <th>Message</th>
                    <th></th>
                </tr>
            </thead>

            <tbody>
                <tr v-for="entry in batchEntriesOfType('exception')">
                    <td>{{truncate(entry.content.message, 120)}}</td>

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

    <!-- Logs -->
    <div class="card mt-5" v-if="batchEntriesOfType('log').length">
        <div class="card-header"><h5>Log Entries</h5></div>

        <table class="table table-hover table-sm mb-0">
            <thead>
                <tr>
                    <th>Message</th>
                    <th></th>
                </tr>
            </thead>

            <tbody>
                <tr v-for="entry in batchEntriesOfType('log')">
                    <td>{{truncate(entry.content.message, 120)}}</td>

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

    <!-- Queries -->
    <div class="card mt-5" v-if="queryEntries.length">
        <div class="card-header"><h5>Queries ({{ queryEntries.length }})</h5></div>

        <table class="table table-hover table-sm mb-0 penultimate-column-right">
            <thead>
                <tr>
                    <th>Query</th>
                    <th>Duration</th>
                    <th></th>
                </tr>
            </thead>

            <tbody>
                <tr v-for="entry in queryEntries">
                    <td>{{truncate(entry.content.sql, 110)}}</td>
                    <td class="table-fit">{{entry.content.time}}ms</td>

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

    <!-- Events -->
    <div class="card mt-5" v-if="batchEntriesOfType('event').length">
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
                <tr v-for="entry in batchEntriesOfType('event')">
                    <td>{{truncate(entry.content.name, 80)}}</td>
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

    <!-- Cache -->
    <div class="card mt-5" v-if="batchEntriesOfType('cache').length">
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
                <tr v-for="entry in batchEntriesOfType('cache')">
                    <td>{{truncate(entry.content.key, 100)}}</td>
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
</div>
</template>
