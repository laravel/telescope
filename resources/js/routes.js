import mailPreview from './screens/mail/preview.vue';
import mailIndex from './screens/mail/index.vue';
import exceptionsPreview from './screens/exceptions/preview.vue';
import exceptionsIndex from './screens/exceptions/index.vue';
import dumpsIndex from './screens/dumps/index.vue';
import logsPreview from './screens/logs/preview.vue';
import logsIndex from './screens/logs/index.vue';
import notificationsPreview from './screens/notifications/preview.vue';
import notificationsIndex from './screens/notifications/index.vue';
import jobsPreview from './screens/jobs/preview.vue';
import jobsIndex from './screens/jobs/index.vue';
import batchesPreview from './screens/batches/preview.vue';
import batchesIndex from './screens/batches/index.vue';
import eventsPreview from './screens/events/preview.vue';
import eventsIndex from './screens/events/index.vue';
import cachePreview from './screens/cache/preview.vue';
import cacheIndex from './screens/cache/index.vue';
import queriesPreview from './screens/queries/preview.vue';
import queriesIndex from './screens/queries/index.vue';
import modelsPreview from './screens/models/preview.vue';
import modelsIndex from './screens/models/index.vue';
import requestsPreview from './screens/requests/preview.vue';
import requestsIndex from './screens/requests/index.vue';
import commandsPreview from './screens/commands/preview.vue';
import commandsIndex from './screens/commands/index.vue';
import schedulePreview from './screens/schedule/preview.vue';
import scheduleIndex from './screens/schedule/index.vue';
import redisPreview from './screens/redis/preview.vue';
import redisIndex from './screens/redis/index.vue';
import monitoringIndex from './screens/monitoring/index.vue';
import gatesPreview from './screens/gates/preview.vue';
import gatesIndex from './screens/gates/index.vue';
import viewsPreview from './screens/views/preview.vue';
import viewsIndex from './screens/views/index.vue';
import clientRequestsPreview from './screens/client-requests/preview.vue';
import clientRequestsIndex from './screens/client-requests/index.vue';

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
        component: mailIndex,
    },

    {
        path: '/exceptions/:id',
        name: 'exception-preview',
        component: exceptionsPreview,
    },

    {
        path: '/exceptions',
        name: 'exceptions',
        component: exceptionsIndex,
    },

    {
        path: '/dumps',
        name: 'dumps',
        component: dumpsIndex,
    },

    {
        path: '/logs/:id',
        name: 'log-preview',
        component: logsPreview,
    },

    {
        path: '/logs',
        name: 'logs',
        component: logsIndex,
    },

    {
        path: '/notifications/:id',
        name: 'notification-preview',
        component: notificationsPreview,
    },

    {
        path: '/notifications',
        name: 'notifications',
        component: notificationsIndex,
    },

    {
        path: '/jobs/:id',
        name: 'job-preview',
        component: jobsPreview,
    },

    {
        path: '/jobs',
        name: 'jobs',
        component: jobsIndex,
    },

    {
        path: '/batches/:id',
        name: 'batch-preview',
        component: batchesPreview,
    },

    {
        path: '/batches',
        name: 'batches',
        component: batchesIndex,
    },

    {
        path: '/events/:id',
        name: 'event-preview',
        component: eventsPreview,
    },

    {
        path: '/events',
        name: 'events',
        component: eventsIndex,
    },

    {
        path: '/cache/:id',
        name: 'cache-preview',
        component: cachePreview,
    },

    {
        path: '/cache',
        name: 'cache',
        component: cacheIndex,
    },

    {
        path: '/queries/:id',
        name: 'query-preview',
        component: queriesPreview,
    },

    {
        path: '/queries',
        name: 'queries',
        component: queriesIndex,
    },

    {
        path: '/models/:id',
        name: 'model-preview',
        component: modelsPreview,
    },

    {
        path: '/models',
        name: 'models',
        component: modelsIndex,
    },

    {
        path: '/requests/:id',
        name: 'request-preview',
        component: requestsPreview,
    },

    {
        path: '/requests',
        name: 'requests',
        component: requestsIndex,
    },

    {
        path: '/commands/:id',
        name: 'command-preview',
        component: commandsPreview,
    },

    {
        path: '/commands',
        name: 'commands',
        component: commandsIndex,
    },

    {
        path: '/schedule/:id',
        name: 'schedule-preview',
        component: schedulePreview,
    },

    {
        path: '/schedule',
        name: 'schedule',
        component: scheduleIndex,
    },

    {
        path: '/redis/:id',
        name: 'redis-preview',
        component: redisPreview,
    },

    {
        path: '/redis',
        name: 'redis',
        component: redisIndex,
    },

    {
        path: '/monitored-tags',
        name: 'monitored-tags',
        component: monitoringIndex,
    },

    {
        path: '/gates/:id',
        name: 'gate-preview',
        component: gatesPreview,
    },

    {
        path: '/gates',
        name: 'gates',
        component: gatesIndex,
    },

    {
        path: '/views/:id',
        name: 'view-preview',
        component: viewsPreview,
    },

    {
        path: '/views',
        name: 'views',
        component: viewsIndex,
    },

    {
        path: '/client-requests/:id',
        name: 'client-request-preview',
        component: clientRequestsPreview,
    },

    {
        path: '/client-requests',
        name: 'client-requests',
        component: clientRequestsIndex,
    },
];
