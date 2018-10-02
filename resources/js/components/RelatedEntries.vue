<script type="text/ecmascript-6">
    import StylesMixin from './../mixins/entriesStyles';


    export default {
        props: ['entry', 'batch'],


        mixins: [
            StylesMixin,
        ],


        /**
         * The component's data.
         */
        data(){
            return {
                currentTab: 'exceptions'
            };
        },


        /**
         * Prepare the component.
         */
        mounted(){
            if (this.exceptions.length) {
                this.currentTab = 'exceptions'
            } else if (this.logs.length) {
                this.currentTab = 'logs'
            } else if (this.queries.length) {
                this.currentTab = 'queries'
            } else if (this.events.length) {
                this.currentTab = 'events'
            } else if (this.cache.length) {
                this.currentTab = 'cache'
            } else if (this.redis.length) {
                this.currentTab = 'redis'
            } else if (this.models.length) {
                this.currentTab = 'models'
            } else if (this.jobs.length) {
                this.currentTab = 'jobs'
            }
        },


        methods: {
            batchEntriesOfType(type) {
                return _.filter(this.batch, {type: type})
            },
        },


        computed: {
            hasRelatedEntries(){
                return !!_.reject(this.batch, entry => {
                    return _.includes(['request', 'command', 'job'], entry.type);
                }).length;
            },

            exceptions() {
                return this.batchEntriesOfType('exception')
            },

            logs() {
                return this.batchEntriesOfType('log')
            },

            queries() {
                return this.batchEntriesOfType('query')
            },

            models() {
                return this.batchEntriesOfType('model')
            },

            events() {
                return this.batchEntriesOfType('event')
            },

            cache() {
                return this.batchEntriesOfType('cache')
            },

            redis() {
                return this.batchEntriesOfType('redis')
            },

            jobs() {
                return this.batchEntriesOfType('job')
            }
        }
    }
</script>

<template>
    <div class="card mt-5" v-if="hasRelatedEntries">
        <ul class="nav nav-pills">
            <li class="nav-item">
                <a class="nav-link" :class="{active: currentTab=='exceptions'}" href="#" v-on:click.prevent="currentTab='exceptions'" v-if="exceptions.length">Exceptions ({{exceptions.length}})</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" :class="{active: currentTab=='logs'}" href="#" v-on:click.prevent="currentTab='logs'" v-if="logs.length">Logs ({{logs.length}})</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" :class="{active: currentTab=='queries'}" href="#" v-on:click.prevent="currentTab='queries'" v-if="queries.length">Queries ({{queries.length}})</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" :class="{active: currentTab=='models'}" href="#" v-on:click.prevent="currentTab='models'" v-if="models.length">Models ({{models.length}})</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" :class="{active: currentTab=='jobs'}" href="#" v-on:click.prevent="currentTab='jobs'" v-if="jobs.length">Jobs ({{jobs.length}})</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" :class="{active: currentTab=='events'}" href="#" v-on:click.prevent="currentTab='events'" v-if="events.length">Events ({{events.length}})</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" :class="{active: currentTab=='cache'}" href="#" v-on:click.prevent="currentTab='cache'" v-if="cache.length">Cache ({{cache.length}})</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" :class="{active: currentTab=='redis'}" href="#" v-on:click.prevent="currentTab='redis'" v-if="redis.length">Redis ({{redis.length}})</a>
            </li>
        </ul>
        <div>
            <!-- Related Exceptions -->
            <table class="table table-hover table-sm mb-0" v-show="currentTab=='exceptions' && exceptions.length">
                <thead>
                <tr>
                    <th>Message</th>
                    <th></th>
                </tr>
                </thead>

                <tbody>
                <tr v-for="entry in exceptions">
                    <td :title="entry.content.message">{{truncate(entry.content.message, 100)}}</td>

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


            <!-- Related Logs -->
            <table class="table table-hover table-sm mb-0" v-show="currentTab=='logs' && logs.length">
                <thead>
                <tr>
                    <th>Message</th>
                    <th scope="col">Level</th>
                    <th></th>
                </tr>
                </thead>

                <tbody>
                <tr v-for="entry in logs">
                    <td :title="entry.content.message">{{truncate(entry.content.message, 100)}}</td>
                    <td class="table-fit">
                        <span class="badge font-weight-light" :class="'badge-'+logLevelClass(entry.content.level)">
                            {{entry.content.level}}
                        </span>
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


            <!-- Related Queries -->
            <table class="table table-hover table-sm mb-0" v-show="currentTab=='queries' && queries.length">
                <thead>
                <tr>
                    <th>Query</th>
                    <th>Duration</th>
                    <th></th>
                </tr>
                </thead>

                <tbody>
                <tr v-for="entry in queries">
                    <td :title="entry.content.sql">{{truncate(entry.content.sql, 110)}}</td>

                    <td class="table-fit">
                        <span class="badge badge-danger font-weight-light" v-if="entry.content.slow">
                            {{entry.content.time}}ms
                        </span>

                        <span v-else>
                            {{entry.content.time}}ms
                        </span>
                    </td>

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

            <!-- Related Model Actions -->
            <table class="table table-hover table-sm mb-0" v-show="currentTab=='models' && models.length">
                <thead>
                <tr>
                    <th>Model</th>
                    <th>Action</th>
                    <th></th>
                </tr>
                </thead>

                <tbody>
                <tr v-for="entry in models">
                    <td :title="entry.content.model">{{truncate(entry.content.model, 100)}}</td>
                    <td class="table-fit">
                        <span class="badge font-weight-light" :class="'badge-'+modelActionClass(entry.content.action)">
                            {{entry.content.action}}
                        </span>
                    </td>

                    <td class="table-fit">
                        <router-link :to="{name:'model-preview', params:{id: entry.id}}" class="control-action">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 16">
                                <path d="M16.56 13.66a8 8 0 0 1-11.32 0L.3 8.7a1 1 0 0 1 0-1.42l4.95-4.95a8 8 0 0 1 11.32 0l4.95 4.95a1 1 0 0 1 0 1.42l-4.95 4.95-.01.01zm-9.9-1.42a6 6 0 0 0 8.48 0L19.38 8l-4.24-4.24a6 6 0 0 0-8.48 0L2.4 8l4.25 4.24h.01zM10.9 12a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm0-2a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"></path>
                            </svg>
                        </router-link>
                    </td>
                </tr>
                </tbody>
            </table>

            <!-- Related Events -->
            <table class="table table-hover table-sm mb-0" v-show="currentTab=='events' && events.length">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Listeners</th>
                    <th></th>
                </tr>
                </thead>

                <tbody>
                <tr v-for="entry in events">
                    <td :title="entry.content.name">
                        {{truncate(entry.content.name, 80)}}

                        <span class="badge badge-info font-weight-light ml-2" v-if="entry.content.broadcast">
                            Broadcast
                        </span>
                    </td>

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


            <!-- Related Cache -->
            <table class="table table-hover table-sm mb-0" v-show="currentTab=='cache' && cache.length">
                <thead>
                <tr>
                    <th>Key</th>
                    <th>Action</th>
                    <th></th>
                </tr>
                </thead>

                <tbody>
                <tr v-for="entry in cache">
                    <td :title="entry.content.key">{{truncate(entry.content.key, 100)}}</td>
                    <td class="table-fit">
                        <span class="badge font-weight-light" :class="'badge-'+cacheActionTypeClass(entry.content.type)">
                            {{entry.content.type}}
                        </span>
                    </td>

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


            <!-- Related Redis Commands -->
            <table class="table table-hover table-sm mb-0" v-show="currentTab=='redis' && redis.length">
                <thead>
                <tr>
                    <th>Command</th>
                    <th>Duration</th>
                    <th></th>
                </tr>
                </thead>

                <tbody>
                <tr v-for="entry in redis">
                    <td :title="entry.content.command">{{truncate(entry.content.command, 100)}}</td>
                    <td class="table-fit">{{entry.content.time}}ms</td>

                    <td class="table-fit">
                        <router-link :to="{name:'redis-preview', params:{id: entry.id}}" class="control-action">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 16">
                                <path d="M16.56 13.66a8 8 0 0 1-11.32 0L.3 8.7a1 1 0 0 1 0-1.42l4.95-4.95a8 8 0 0 1 11.32 0l4.95 4.95a1 1 0 0 1 0 1.42l-4.95 4.95-.01.01zm-9.9-1.42a6 6 0 0 0 8.48 0L19.38 8l-4.24-4.24a6 6 0 0 0-8.48 0L2.4 8l4.25 4.24h.01zM10.9 12a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm0-2a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"></path>
                            </svg>
                        </router-link>
                    </td>
                </tr>
                </tbody>
            </table>


            <!-- Related Jobs -->
            <table class="table table-hover table-sm mb-0" v-show="currentTab=='jobs' && jobs.length">
                <thead>
                <tr>
                    <th>Job</th>
                    <th>Connection</th>
                    <th>Queue</th>
                    <th scope="col">Status</th>
                    <th></th>
                </tr>
                </thead>

                <tbody>
                <tr v-for="entry in jobs">
                    <td :title="entry.content.name">{{truncate(entry.content.name, 100)}}</td>
                    <td class="table-fit">{{truncate(entry.content.connection, 10)}}</td>
                    <td class="table-fit">{{truncate(entry.content.queue, 10)}}</td>

                    <td class="table-fit">
                        <span class="badge font-weight-light" :class="'badge-'+jobStatusClass(entry.content.status)">
                            {{entry.content.status}}
                        </span>
                    </td>

                    <td class="table-fit">
                        <router-link :to="{name:'job-preview', params:{id: entry.id}}" class="control-action">
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
