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
            this.activateFirstTab();
        },


        watch: {
            entry(){
                this.activateFirstTab();
            }
        },


        methods: {
            activateFirstTab(){
                if (window.location.hash) {
                    this.currentTab = window.location.hash.substring(1);
                } else if (this.exceptions.length) {
                    this.currentTab = 'exceptions'
                } else if (this.logs.length) {
                    this.currentTab = 'logs'
                } else if (this.views.length) {
                    this.currentTab = 'views'
                } else if (this.queries.length) {
                    this.currentTab = 'queries'
                } else if (this.models.length) {
                    this.currentTab = 'models'
                } else if (this.jobs.length) {
                    this.currentTab = 'jobs'
                } else if (this.mails.length) {
                    this.currentTab = 'mails'
                } else if (this.notifications.length) {
                    this.currentTab = 'notifications'
                } else if (this.events.length) {
                    this.currentTab = 'events'
                } else if (this.cache.length) {
                    this.currentTab = 'cache'
                } else if (this.gates.length) {
                    this.currentTab = 'gates'
                } else if (this.redis.length) {
                    this.currentTab = 'redis'
                }
            },

            activateTab(tab){
                this.currentTab = tab;
                if(window.history.replaceState) {
                    window.history.replaceState(null, null, '#' + this.currentTab);
                }
            }
        },


        computed: {
            hasRelatedEntries(){
                return !!_.reject(this.batch, entry => {
                    return _.includes(['request', 'command'], entry.type);
                }).length;
            },

            entryTypesAvailable(){
                return _.uniqBy(this.batch, 'type').length;
            },

            exceptions() {
                return _.filter(this.batch, {type: 'exception'});
            },

            gates() {
                return _.filter(this.batch, {type: 'gate'});
            },

            logs() {
                return _.filter(this.batch, {type: 'log'});
            },

            queries() {
                return _.filter(this.batch, {type: 'query'});
            },

            models() {
                return _.filter(this.batch, {type: 'model'});
            },

            jobs() {
                return _.filter(this.batch, {type: 'job'});
            },

            events() {
                return _.filter(this.batch, {type: 'event'});
            },

            cache() {
                return _.filter(this.batch, {type: 'cache'});
            },

            redis() {
                return _.filter(this.batch, {type: 'redis'});
            },

            mails() {
                return _.filter(this.batch, {type: 'mail'});
            },

            notifications() {
                return _.filter(this.batch, {type: 'notification'});
            },

            views() {
                return _.filter(this.batch, {type: 'view'});
            },

            queriesSummary() {
                return {
                    time: _.reduce(this.queries, (time, q) => { return time + parseFloat(q.content.time) }, 0.00).toFixed(2),
                    duplicated: this.queries.length - _.size(_.groupBy(this.queries, (q) => { return q.content.hash })),
                };
            },

            tabs(){
                return _.filter([
                    {title: "Exceptions", type: "exceptions", count: this.exceptions.length},
                    {title: "Logs", type: "logs", count: this.logs.length},
                    {title: "Views", type: "views", count: this.views.length},
                    {title: "Queries", type: "queries", count: this.queries.length},
                    {title: "Models", type: "models", count: this.models.length},
                    {title: "Gates", type: "gates", count: this.gates.length},
                    {title: "Jobs", type: "jobs", count: this.jobs.length},
                    {title: "Mail", type: "mails", count: this.mails.length},
                    {title: "Notifications", type: "notifications", count: this.notifications.length},
                    {title: "Events", type: "events", count: this.events.length},
                    {title: "Cache", type: "cache", count: this.cache.length},
                    {title: "Redis", type: "redis", count: this.redis.length},
                ], tab => tab.count > 0);
            },

            separateTabs(){
                return _.slice(this.tabs, 0, 7);
            },

            dropdownTabs(){
                return _.slice(this.tabs, 7, 10);
            },

            dropdownTabSelected(){
                return _.includes(_.map(this.dropdownTabs, 'type'), this.currentTab);
            }
        }
    }
</script>

<template>
    <div class="card mt-5" v-if="hasRelatedEntries">
        <ul class="nav nav-pills">
            <li class="nav-item" v-for="tab in separateTabs">
                <a class="nav-link" :class="{active: currentTab==tab.type}" href="#" v-on:click.prevent="activateTab(tab.type)" v-if="tab.count">
                    {{tab.title}} ({{tab.count}})
                </a>
            </li>
            <li class="nav-item dropdown" v-if="dropdownTabs.length">
                <a class="nav-link dropdown-toggle" :class="{active: dropdownTabSelected}" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">More</a>
                <div class="dropdown-menu">
                    <a class="dropdown-item" :class="{active: currentTab==tab.type}" href="#" v-for="tab in dropdownTabs" v-on:click.prevent="activateTab(tab.type)">{{tab.title}} ({{tab.count}})</a>
                </div>
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
                        <td :title="entry.content.class">
                            {{truncate(entry.content.class, 70)}}<br>
                            <small class="text-muted">{{truncate(entry.content.message, 100)}}</small>
                        </td>

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
                    <td :title="entry.content.message">{{truncate(entry.content.message, 90)}}</td>
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
                    <th>Query<br/><small>{{ queries.length }} queries, {{ queriesSummary.duplicated }} of which are duplicated.</small></th>
                    <th>Duration<br/><small>{{ queriesSummary.time }}ms</small></th>
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

            <!-- Related Gates -->
            <table class="table table-hover table-sm mb-0" v-show="currentTab=='gates' && gates.length">
                <thead>
                <tr>
                    <th>Ability</th>
                    <th>Result</th>
                    <th></th>
                </tr>
                </thead>

                <tbody>
                <tr v-for="entry in gates">
                    <td :title="entry.content.ability">{{truncate(entry.content.ability, 80)}}</td>
                    <td class="table-fit">
                        <span class="badge font-weight-light" :class="'badge-'+gateResultClass(entry.content.result)">
                            {{entry.content.result}}
                        </span>
                    </td>

                    <td class="table-fit">
                        <router-link :to="{name:'gate-preview', params:{id: entry.id}}" class="control-action">
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
                    <th scope="col">Status</th>
                    <th></th>
                </tr>
                </thead>

                <tbody>
                <tr v-for="entry in jobs">
                    <td>
                        <span :title="entry.content.name">{{truncate(entry.content.name, 68)}}</span><br>
                        <small class="text-muted">
                            Connection: {{entry.content.connection}} | Queue: {{entry.content.queue}}
                        </small>
                    </td>

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


            <!-- Related Mail -->
            <table class="table table-hover table-sm mb-0" v-show="currentTab=='mails' && mails.length">
                <thead>
                <tr>
                    <th>Mailable</th>
                    <th></th>
                </tr>
                </thead>

                <tbody>
                <tr v-for="entry in mails">
                    <td>
                        <span :title="entry.content.mailable">{{truncate(entry.content.mailable || '-', 70)}}</span>

                        <span class="badge badge-secondary font-weight-light ml-2" v-if="entry.content.queued">
                            Queued
                        </span>

                        <br>

                        <small class="text-muted" :title="entry.content.subject">
                            Subject: {{truncate(entry.content.subject, 90)}}
                        </small>
                    </td>

                    <td class="table-fit">
                        <router-link :to="{name:'mail-preview', params:{id: entry.id}}" class="control-action">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 16">
                                <path d="M16.56 13.66a8 8 0 0 1-11.32 0L.3 8.7a1 1 0 0 1 0-1.42l4.95-4.95a8 8 0 0 1 11.32 0l4.95 4.95a1 1 0 0 1 0 1.42l-4.95 4.95-.01.01zm-9.9-1.42a6 6 0 0 0 8.48 0L19.38 8l-4.24-4.24a6 6 0 0 0-8.48 0L2.4 8l4.25 4.24h.01zM10.9 12a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm0-2a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"></path>
                            </svg>
                        </router-link>
                    </td>
                </tr>
                </tbody>
            </table>


            <!-- Related Notifications -->
            <table class="table table-hover table-sm mb-0" v-show="currentTab=='notifications' && notifications.length">
                <thead>
                <tr>
                    <th>Notification</th>
                    <th>Channel</th>
                    <th></th>
                </tr>
                </thead>

                <tbody>
                <tr v-for="entry in notifications">
                    <td>
                        <span :title="entry.content.notification">{{truncate(entry.content.notification || '-', 70)}}</span>

                        <span class="badge badge-secondary font-weight-light ml-2" v-if="entry.content.queued">
                            Queued
                        </span>

                        <br>

                        <small class="text-muted" :title="entry.content.notifiable">
                            Recipient: {{truncate(entry.content.notifiable, 90)}}
                        </small>
                    </td>

                    <td class="table-fit">{{truncate(entry.content.channel, 20)}}</td>

                    <td class="table-fit">
                        <router-link :to="{name:'notification-preview', params:{id: entry.id}}" class="control-action">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 16">
                                <path d="M16.56 13.66a8 8 0 0 1-11.32 0L.3 8.7a1 1 0 0 1 0-1.42l4.95-4.95a8 8 0 0 1 11.32 0l4.95 4.95a1 1 0 0 1 0 1.42l-4.95 4.95-.01.01zm-9.9-1.42a6 6 0 0 0 8.48 0L19.38 8l-4.24-4.24a6 6 0 0 0-8.48 0L2.4 8l4.25 4.24h.01zM10.9 12a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm0-2a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"></path>
                            </svg>
                        </router-link>
                    </td>
                </tr>
                </tbody>
            </table>

            <!-- Related Views -->
            <table class="table table-hover table-sm mb-0" v-show="currentTab=='views' && views.length">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Composers</th>
                    <th></th>
                </tr>
                </thead>

                <tbody>
                <tr v-for="entry in views">
                    <td>
                        {{entry.content.name}} <br/>
                        <small class="text-muted">{{truncate(entry.content.path, 100)}}</small>
                    </td>

                    <td class="table-fit">
                        {{entry.content.composers ? entry.content.composers.length : 0}}
                    </td>

                    <td class="table-fit">
                        <router-link :to="{name:'view-preview', params:{id: entry.id}}" class="control-action">
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

<style scoped>
    td {
        vertical-align: middle !important;
    }
</style>
