import mail from './screens/mail/index.vue';
import mailPreview from './screens/mail/preview.vue';
import exceptions from './screens/exceptions/index.vue';
import exceptionsPreview from './screens/exceptions/preview.vue';
import dumps from './screens/dumps/index.vue';
import logs from './screens/logs/index.vue';
import logsPreview from './screens/logs/preview.vue';
import notifications from './screens/notifications/index.vue';
import notificationsPreview from './screens/notifications/preview.vue';
import jobs from './screens/jobs/index.vue';
import jobsPreview from './screens/jobs/preview.vue';
import batches from './screens/batches/index.vue';
import batchesPreview from './screens/batches/preview.vue';
import events from './screens/events/index.vue';
import eventsPreview from './screens/events/preview.vue';
import cache from './screens/cache/index.vue';
import cachePreview from './screens/cache/preview.vue';
import queries from './screens/queries/index.vue';
import queriesPreview from './screens/queries/preview.vue';
import models from './screens/models/index.vue';
import modelsPreview from './screens/models/preview.vue';
import requests from './screens/requests/index.vue';
import requestsPreview from './screens/requests/preview.vue';
import commands from './screens/commands/index.vue';
import commandsPreview from './screens/commands/preview.vue';
import schedule from './screens/schedule/index.vue';
import schedulePreview from './screens/schedule/preview.vue';
import redis from './screens/redis/index.vue';
import redisPreview from './screens/redis/preview.vue';
import monitoring from './screens/monitoring/index.vue';
import gates from './screens/gates/index.vue';
import gatesPreview from './screens/gates/preview.vue';
import views from './screens/views/index.vue';
import viewsPreview from './screens/views/preview.vue';
import clientRequests from './screens/client-requests/index.vue';
import clientRequestsPreview from './screens/client-requests/preview.vue';

export default [
    { path: '/', redirect: '/requests' },

    {
        path: '/mail',
        children: [
            { path: '', name: 'mail', component: mail },
            { path: ':id', name: 'mail-preview', component: mailPreview },
        ],
    },

    {
        path: '/exceptions',
        children: [
            { path: '', name: 'exceptions', component: exceptions },
            { path: ':id', name: 'exception-preview', component: exceptionsPreview },
        ],
    },

    {
        path: '/dumps',
        name: 'dumps',
        component: dumps,
    },

    {
        path: '/logs',
        children: [
            { path: '', name: 'logs', component: logs },
            { path: ':id', name: 'log-preview', component: logsPreview },
        ],
    },

    {
        path: '/notifications',
        children: [
            { path: '', name: 'notifications', component: notifications },
            { path: ':id', name: 'notification-preview', component: notificationsPreview },
        ],
    },

    {
        path: '/jobs',
        children: [
            { path: '', name: 'jobs', component: jobs },
            { path: ':id', name: 'job-preview', component: jobsPreview },
        ],
    },

    {
        path: '/batches',
        children: [
            { path: '', name: 'batches', component: batches },
            { path: ':id', name: 'batch-preview', component: batchesPreview },
        ],
    },

    {
        path: '/events',
        children: [
            { path: '', name: 'events', component: events },
            { path: ':id', name: 'event-preview', component: eventsPreview },
        ],
    },

    {
        path: '/cache',
        children: [
            { path: '', name: 'cache', component: cache },
            { path: ':id', name: 'cache-preview', component: cachePreview },
        ],
    },

    {
        path: '/queries',
        children: [
            { path: '', name: 'queries', component: queries },
            { path: ':id', name: 'query-preview', component: queriesPreview },
        ],
    },

    {
        path: '/models',
        children: [
            { path: '', name: 'models', component: models },
            { path: ':id', name: 'model-preview', component: modelsPreview },
        ],
    },

    {
        path: '/requests',
        children: [
            { path: '', name: 'requests', component: requests },
            { path: ':id', name: 'request-preview', component: requestsPreview },
        ],
    },

    {
        path: '/commands',
        children: [
            { path: '', name: 'commands', component: commands },
            { path: ':id', name: 'command-preview', component: commandsPreview },
        ],
    },

    {
        path: '/schedule',
        children: [
            { path: '', name: 'schedule', component: schedule },
            { path: ':id', name: 'schedule-preview', component: schedulePreview },
        ],
    },

    {
        path: '/redis',
        children: [
            { path: '', name: 'redis', component: redis },
            { path: ':id', name: 'redis-preview', component: redisPreview },
        ],
    },

    {
        path: '/monitored-tags',
        name: 'monitored-tags',
        component: monitoring,
    },

    {
        path: '/gates',
        children: [
            { path: '', name: 'gates', component: gates },
            { path: ':id', name: 'gate-preview', component: gatesPreview },
        ],
    },

    {
        path: '/views',
        children: [
            { path: '', name: 'views', component: views },
            { path: ':id', name: 'view-preview', component: viewsPreview },
        ],
    },

    {
        path: '/client-requests',
        children: [
            { path: '', name: 'client-requests', component: clientRequests },
            { path: ':id', name: 'client-request-preview', component: clientRequestsPreview },
        ],
    },
];
