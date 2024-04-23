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
        path: '/mail/:id',
        name: 'mail-preview',
        component: mailPreview,
    },

    {
        path: '/mail',
        name: 'mail',
        component: mail,
    },

    {
        path: '/exceptions/:id',
        name: 'exception-preview',
        component: exceptionsPreview,
    },

    {
        path: '/exceptions',
        name: 'exceptions',
        component: exceptions,
    },

    {
        path: '/dumps',
        name: 'dumps',
        component: dumps,
    },

    {
        path: '/logs/:id',
        name: 'log-preview',
        component: logsPreview,
    },

    {
        path: '/logs',
        name: 'logs',
        component: logs,
    },

    {
        path: '/notifications/:id',
        name: 'notification-preview',
        component: notificationsPreview,
    },

    {
        path: '/notifications',
        name: 'notifications',
        component: notifications,
    },

    {
        path: '/jobs/:id',
        name: 'job-preview',
        component: jobsPreview,
    },

    {
        path: '/jobs',
        name: 'jobs',
        component: jobs,
    },

    {
        path: '/batches/:id',
        name: 'batch-preview',
        component: batchesPreview,
    },

    {
        path: '/batches',
        name: 'batches',
        component: batches,
    },

    {
        path: '/events/:id',
        name: 'event-preview',
        component: eventsPreview,
    },

    {
        path: '/events',
        name: 'events',
        component: events,
    },

    {
        path: '/cache/:id',
        name: 'cache-preview',
        component: cachePreview,
    },

    {
        path: '/cache',
        name: 'cache',
        component: cache,
    },

    {
        path: '/queries/:id',
        name: 'query-preview',
        component: queriesPreview,
    },

    {
        path: '/queries',
        name: 'queries',
        component: queries,
    },

    {
        path: '/models/:id',
        name: 'model-preview',
        component: modelsPreview,
    },

    {
        path: '/models',
        name: 'models',
        component: models,
    },

    {
        path: '/requests/:id',
        name: 'request-preview',
        component: requestsPreview,
    },

    {
        path: '/requests',
        name: 'requests',
        component: requests,
    },

    {
        path: '/commands/:id',
        name: 'command-preview',
        component: commandsPreview,
    },

    {
        path: '/commands',
        name: 'commands',
        component: commands,
    },

    {
        path: '/schedule/:id',
        name: 'schedule-preview',
        component: schedulePreview,
    },

    {
        path: '/schedule',
        name: 'schedule',
        component: schedule,
    },

    {
        path: '/redis/:id',
        name: 'redis-preview',
        component: redisPreview,
    },

    {
        path: '/redis',
        name: 'redis',
        component: redis,
    },

    {
        path: '/monitored-tags',
        name: 'monitored-tags',
        component: monitoring,
    },

    {
        path: '/gates/:id',
        name: 'gate-preview',
        component: gatesPreview,
    },

    {
        path: '/gates',
        name: 'gates',
        component: gates,
    },

    {
        path: '/views/:id',
        name: 'view-preview',
        component: viewsPreview,
    },

    {
        path: '/views',
        name: 'views',
        component: views,
    },

    {
        path: '/client-requests/:id',
        name: 'client-request-preview',
        component: clientRequestsPreview,
    },

    {
        path: '/client-requests',
        name: 'client-requests',
        component: clientRequests,
    },
];
