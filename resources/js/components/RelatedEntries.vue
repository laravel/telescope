<script type="text/ecmascript-6">
import _ from 'lodash';
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
            } else if (this.clientRequests.length) {
                this.currentTab = 'client_requests';
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

        clientRequests() {
            return _.filter(this.batch, {type: 'client_request'});
        },

        queriesSummary() {
            return {
                time: _.reduce(this.queries, (time, q) => { return time + parseFloat(q.content.time) }, 0.00).toFixed(2),
                duplicated: this.queries.length - _.size(_.groupBy(this.queries, (q) => { return `${q.content.hash}-${q.content.connection}` })),
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
                {title: "HTTP Client", type: "client_requests", count: this.clientRequests.length},
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
    <div class="card overflow-hidden mt-5" v-if="hasRelatedEntries">
        <ul class="nav nav-pills">
            <li class="nav-item" v-for="tab in separateTabs">
                <a
                    class="nav-link"
                    :class="{ active: currentTab == tab.type }"
                    href="#"
                    v-on:click.prevent="activateTab(tab.type)"
                    v-if="tab.count"
                >
                    {{ tab.title }} ({{ tab.count }})
                </a>
            </li>
            <li class="nav-item dropdown" v-if="dropdownTabs.length">
                <a
                    class="nav-link dropdown-toggle"
                    :class="{ active: dropdownTabSelected }"
                    data-toggle="dropdown"
                    href="#"
                    role="button"
                    aria-haspopup="true"
                    aria-expanded="false"
                    >More</a
                >
                <div class="dropdown-menu">
                    <a
                        class="dropdown-item"
                        :class="{ active: currentTab == tab.type }"
                        href="#"
                        v-for="tab in dropdownTabs"
                        v-on:click.prevent="activateTab(tab.type)"
                        >{{ tab.title }} ({{ tab.count }})</a
                    >
                </div>
            </li>
        </ul>
        <div>
            <!-- Related Exceptions -->
            <table class="table table-hover mb-0" v-show="currentTab == 'exceptions' && exceptions.length">
                <thead>
                    <tr>
                        <th>Message</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="entry in exceptions">
                        <td :title="entry.content.class">
                            {{ truncate(entry.content.class, 70) }}<br />
                            <small class="text-muted text-break">{{ truncate(entry.content.message, 200) }}</small>
                        </td>

                        <td class="table-fit">
                            <router-link
                                :to="{
                                    name: 'exception-preview',
                                    params: { id: entry.id },
                                }"
                                class="control-action"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM6.75 9.25a.75.75 0 000 1.5h4.59l-2.1 1.95a.75.75 0 001.02 1.1l3.5-3.25a.75.75 0 000-1.1l-3.5-3.25a.75.75 0 10-1.02 1.1l2.1 1.95H6.75z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </router-link>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Related Logs -->
            <table class="table table-hover mb-0" v-show="currentTab == 'logs' && logs.length">
                <thead>
                    <tr>
                        <th>Message</th>
                        <th scope="col">Level</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="entry in logs">
                        <td :title="entry.content.message">
                            {{ truncate(entry.content.message, 90) }}
                        </td>
                        <td class="table-fit">
                            <span class="badge" :class="'badge-' + logLevelClass(entry.content.level)">
                                {{ entry.content.level }}
                            </span>
                        </td>

                        <td class="table-fit">
                            <router-link
                                :to="{
                                    name: 'log-preview',
                                    params: { id: entry.id },
                                }"
                                class="control-action"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM6.75 9.25a.75.75 0 000 1.5h4.59l-2.1 1.95a.75.75 0 001.02 1.1l3.5-3.25a.75.75 0 000-1.1l-3.5-3.25a.75.75 0 10-1.02 1.1l2.1 1.95H6.75z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </router-link>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Related Queries -->
            <table class="table table-hover mb-0" v-show="currentTab == 'queries' && queries.length">
                <thead>
                    <tr>
                        <th>
                            Query<br /><small
                                >{{ queries.length }} queries, {{ queriesSummary.duplicated }} of which are
                                duplicated.</small
                            >
                        </th>
                        <th class="text-right">
                            Duration<br /><small>{{ queriesSummary.time }}ms</small>
                        </th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="entry in queries">
                        <td :title="entry.content.sql">
                            <code>{{ truncate(entry.content.sql, 110) }}</code>
                        </td>

                        <td class="table-fit text-right">
                            <span class="badge badge-danger" v-if="entry.content.slow">
                                {{ entry.content.time }}ms
                            </span>

                            <span v-else class="text-muted"> {{ entry.content.time }}ms </span>
                        </td>

                        <td class="table-fit">
                            <router-link
                                :to="{
                                    name: 'query-preview',
                                    params: { id: entry.id },
                                }"
                                class="control-action"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM6.75 9.25a.75.75 0 000 1.5h4.59l-2.1 1.95a.75.75 0 001.02 1.1l3.5-3.25a.75.75 0 000-1.1l-3.5-3.25a.75.75 0 10-1.02 1.1l2.1 1.95H6.75z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </router-link>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Related Model Actions -->
            <table class="table table-hover mb-0" v-show="currentTab == 'models' && models.length">
                <thead>
                    <tr>
                        <th>Model</th>
                        <th>Hydrated</th>
                        <th>Action</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="entry in models">
                        <td :title="entry.content.model">
                            {{ truncate(entry.content.model, 100) }}
                        </td>
                        <td>{{ entry.content?.count }}</td>
                        <td class="table-fit">
                            <span class="badge" :class="'badge-' + modelActionClass(entry.content.action)">
                                {{ entry.content.action }}
                            </span>
                        </td>

                        <td class="table-fit">
                            <router-link
                                :to="{
                                    name: 'model-preview',
                                    params: { id: entry.id },
                                }"
                                class="control-action"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM6.75 9.25a.75.75 0 000 1.5h4.59l-2.1 1.95a.75.75 0 001.02 1.1l3.5-3.25a.75.75 0 000-1.1l-3.5-3.25a.75.75 0 10-1.02 1.1l2.1 1.95H6.75z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </router-link>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Related Gates -->
            <table class="table table-hover mb-0" v-show="currentTab == 'gates' && gates.length">
                <thead>
                    <tr>
                        <th>Ability</th>
                        <th>Result</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="entry in gates">
                        <td :title="entry.content.ability">
                            {{ truncate(entry.content.ability, 80) }}
                        </td>
                        <td class="table-fit">
                            <span class="badge" :class="'badge-' + gateResultClass(entry.content.result)">
                                {{ entry.content.result }}
                            </span>
                        </td>

                        <td class="table-fit">
                            <router-link
                                :to="{
                                    name: 'gate-preview',
                                    params: { id: entry.id },
                                }"
                                class="control-action"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM6.75 9.25a.75.75 0 000 1.5h4.59l-2.1 1.95a.75.75 0 001.02 1.1l3.5-3.25a.75.75 0 000-1.1l-3.5-3.25a.75.75 0 10-1.02 1.1l2.1 1.95H6.75z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </router-link>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Related Jobs -->
            <table class="table table-hover mb-0" v-show="currentTab == 'jobs' && jobs.length">
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
                            <span :title="entry.content.name">{{ truncate(entry.content.name, 68) }}</span
                            ><br />
                            <small class="text-muted">
                                Connection: {{ entry.content.connection }} | Queue: {{ entry.content.queue }}
                            </small>
                        </td>

                        <td class="table-fit">
                            <span class="badge" :class="'badge-' + jobStatusClass(entry.content.status)">
                                {{ entry.content.status }}
                            </span>
                        </td>

                        <td class="table-fit">
                            <router-link
                                :to="{
                                    name: 'job-preview',
                                    params: { id: entry.id },
                                }"
                                class="control-action"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM6.75 9.25a.75.75 0 000 1.5h4.59l-2.1 1.95a.75.75 0 001.02 1.1l3.5-3.25a.75.75 0 000-1.1l-3.5-3.25a.75.75 0 10-1.02 1.1l2.1 1.95H6.75z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </router-link>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Related Events -->
            <table class="table table-hover mb-0" v-show="currentTab == 'events' && events.length">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th class="text-right">Listeners</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="entry in events">
                        <td :title="entry.content.name">
                            {{ truncate(entry.content.name, 80) }}

                            <span class="badge badge-info ml-2" v-if="entry.content.broadcast"> Broadcast </span>
                        </td>

                        <td class="table-fit text-right text-muted">
                            {{ entry.content.listeners.length }}
                        </td>

                        <td class="table-fit">
                            <router-link
                                :to="{
                                    name: 'event-preview',
                                    params: { id: entry.id },
                                }"
                                class="control-action"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM6.75 9.25a.75.75 0 000 1.5h4.59l-2.1 1.95a.75.75 0 001.02 1.1l3.5-3.25a.75.75 0 000-1.1l-3.5-3.25a.75.75 0 10-1.02 1.1l2.1 1.95H6.75z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </router-link>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Related Cache -->
            <table class="table table-hover mb-0" v-show="currentTab == 'cache' && cache.length">
                <thead>
                    <tr>
                        <th>Key</th>
                        <th>Action</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="entry in cache">
                        <td :title="entry.content.key">
                            {{ truncate(entry.content.key, 100) }}
                        </td>
                        <td class="table-fit">
                            <span class="badge" :class="'badge-' + cacheActionTypeClass(entry.content.type)">
                                {{ entry.content.type }}
                            </span>
                        </td>

                        <td class="table-fit">
                            <router-link
                                :to="{
                                    name: 'cache-preview',
                                    params: { id: entry.id },
                                }"
                                class="control-action"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM6.75 9.25a.75.75 0 000 1.5h4.59l-2.1 1.95a.75.75 0 001.02 1.1l3.5-3.25a.75.75 0 000-1.1l-3.5-3.25a.75.75 0 10-1.02 1.1l2.1 1.95H6.75z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </router-link>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Related Redis Commands -->
            <table class="table table-hover mb-0" v-show="currentTab == 'redis' && redis.length">
                <thead>
                    <tr>
                        <th>Command</th>
                        <th class="text-right">Duration</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="entry in redis">
                        <td :title="entry.content.command">
                            {{ truncate(entry.content.command, 100) }}
                        </td>
                        <td class="table-fit text-right text-muted">{{ entry.content.time }}ms</td>

                        <td class="table-fit">
                            <router-link
                                :to="{
                                    name: 'redis-preview',
                                    params: { id: entry.id },
                                }"
                                class="control-action"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM6.75 9.25a.75.75 0 000 1.5h4.59l-2.1 1.95a.75.75 0 001.02 1.1l3.5-3.25a.75.75 0 000-1.1l-3.5-3.25a.75.75 0 10-1.02 1.1l2.1 1.95H6.75z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </router-link>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Related Mail -->
            <table class="table table-hover mb-0" v-show="currentTab == 'mails' && mails.length">
                <thead>
                    <tr>
                        <th>Mailable</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="entry in mails">
                        <td>
                            <span :title="entry.content.mailable">{{
                                truncate(entry.content.mailable || '-', 70)
                            }}</span>

                            <span class="badge badge-secondary ml-2" v-if="entry.content.queued"> Queued </span>

                            <br />

                            <small class="text-muted" :title="entry.content.subject">
                                Subject:
                                {{ truncate(entry.content.subject, 90) }}
                            </small>
                        </td>

                        <td class="table-fit">
                            <router-link
                                :to="{
                                    name: 'mail-preview',
                                    params: { id: entry.id },
                                }"
                                class="control-action"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM6.75 9.25a.75.75 0 000 1.5h4.59l-2.1 1.95a.75.75 0 001.02 1.1l3.5-3.25a.75.75 0 000-1.1l-3.5-3.25a.75.75 0 10-1.02 1.1l2.1 1.95H6.75z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </router-link>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Related Notifications -->
            <table class="table table-hover mb-0" v-show="currentTab == 'notifications' && notifications.length">
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
                            <span :title="entry.content.notification">{{
                                truncate(entry.content.notification || '-', 70)
                            }}</span>

                            <span class="badge badge-secondary ml-2" v-if="entry.content.queued"> Queued </span>

                            <br />

                            <small class="text-muted" :title="entry.content.notifiable">
                                Recipient:
                                {{ truncate(entry.content.notifiable, 90) }}
                            </small>
                        </td>

                        <td class="table-fit text-muted">
                            {{ truncate(entry.content.channel, 20) }}
                        </td>

                        <td class="table-fit">
                            <router-link
                                :to="{
                                    name: 'notification-preview',
                                    params: { id: entry.id },
                                }"
                                class="control-action"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM6.75 9.25a.75.75 0 000 1.5h4.59l-2.1 1.95a.75.75 0 001.02 1.1l3.5-3.25a.75.75 0 000-1.1l-3.5-3.25a.75.75 0 10-1.02 1.1l2.1 1.95H6.75z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </router-link>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Related Views -->
            <table class="table table-hover mb-0" v-show="currentTab == 'views' && views.length">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th class="text-right">Composers</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="entry in views">
                        <td>
                            {{ entry.content.name }} <br />
                            <small class="text-muted">{{ truncate(entry.content.path, 100) }}</small>
                        </td>

                        <td class="table-fit text-right text-muted">
                            {{ entry.content.composers ? entry.content.composers.length : 0 }}
                        </td>

                        <td class="table-fit">
                            <router-link
                                :to="{
                                    name: 'view-preview',
                                    params: { id: entry.id },
                                }"
                                class="control-action"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM6.75 9.25a.75.75 0 000 1.5h4.59l-2.1 1.95a.75.75 0 001.02 1.1l3.5-3.25a.75.75 0 000-1.1l-3.5-3.25a.75.75 0 10-1.02 1.1l2.1 1.95H6.75z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </router-link>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Related Http Client Requests -->
            <table class="table table-hover mb-0" v-show="currentTab == 'client_requests' && clientRequests.length">
                <thead>
                    <tr>
                        <th>Verb</th>
                        <th>URI</th>
                        <th>Status</th>
                        <th class="text-right">Happened</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="entry in clientRequests">
                        <td class="table-fit pr-0">
                            <span class="badge" :class="'badge-' + requestMethodClass(entry.content.method)">
                                {{ entry.content.method }}
                            </span>
                        </td>

                        <td :title="entry.content.uri">
                            {{ truncate(entry.content.uri, 60) }}
                        </td>

                        <td class="table-fit">
                            <span
                                class="badge"
                                :class="
                                    'badge-' +
                                    requestStatusClass(
                                        entry.content.response_status !== undefined
                                            ? entry.content.response_status
                                            : null
                                    )
                                "
                            >
                                {{
                                    entry.content.response_status !== undefined ? entry.content.response_status : 'N/A'
                                }}
                            </span>
                        </td>

                        <td
                            class="table-fit text-right text-muted"
                            :data-timeago="entry.created_at"
                            :title="entry.created_at"
                        >
                            {{ timeAgo(entry.created_at) }}
                        </td>

                        <td class="table-fit">
                            <router-link
                                :to="{
                                    name: 'client-request-preview',
                                    params: { id: entry.id },
                                }"
                                class="control-action"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM6.75 9.25a.75.75 0 000 1.5h4.59l-2.1 1.95a.75.75 0 001.02 1.1l3.5-3.25a.75.75 0 000-1.1l-3.5-3.25a.75.75 0 10-1.02 1.1l2.1 1.95H6.75z"
                                        clip-rule="evenodd"
                                    />
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
