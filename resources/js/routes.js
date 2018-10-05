export default [
    {path: '/', redirect: '/requests'},

    {
        path: '/mail/:id',
        name: 'mail-preview',
        component: require('./screens/mail/preview')
    },

    {
        path: '/mail',
        name: 'mail-index',
        component: require('./screens/mail/index')
    },

    {
        path: '/exceptions/:id',
        name: 'exception-preview',
        component: require('./screens/exceptions/preview')
    },

    {
        path: '/exceptions',
        name: 'exceptions',
        component: require('./screens/exceptions/index')
    },

    {
        path: '/dumps',
        name: 'dumps',
        component: require('./screens/dumps/index')
    },

    {
        path: '/logs/:id',
        name: 'log-preview',
        component: require('./screens/logs/preview')
    },

    {
        path: '/logs',
        name: 'logs',
        component: require('./screens/logs/index')
    },

    {
        path: '/notifications/:id',
        name: 'notification-preview',
        component: require('./screens/notifications/preview')
    },

    {
        path: '/notifications',
        name: 'notifications',
        component: require('./screens/notifications/index')
    },

    {
        path: '/jobs/:id',
        name: 'job-preview',
        component: require('./screens/jobs/preview')
    },

    {
        path: '/jobs',
        name: 'jobs',
        component: require('./screens/jobs/index')
    },

    {
        path: '/events/:id',
        name: 'event-preview',
        component: require('./screens/events/preview')
    },

    {
        path: '/events',
        name: 'events',
        component: require('./screens/events/index')
    },

    {
        path: '/cache/:id',
        name: 'cache-preview',
        component: require('./screens/cache/preview')
    },

    {
        path: '/cache',
        name: 'cache',
        component: require('./screens/cache/index')
    },

    {
        path: '/queries/:id',
        name: 'query-preview',
        component: require('./screens/queries/preview')
    },

    {
        path: '/queries',
        name: 'queries',
        component: require('./screens/queries/index')
    },

    {
        path: '/models/:id',
        name: 'model-preview',
        component: require('./screens/models/preview')
    },

    {
        path: '/models',
        name: 'models',
        component: require('./screens/models/index')
    },

    {
        path: '/requests/:id',
        name: 'request-preview',
        component: require('./screens/requests/preview')
    },

    {
        path: '/requests',
        name: 'requests',
        component: require('./screens/requests/index')
    },

    {
        path: '/commands/:id',
        name: 'command-preview',
        component: require('./screens/commands/preview')
    },

    {
        path: '/commands',
        name: 'commands',
        component: require('./screens/commands/index')
    },

    {
        path: '/schedule/:id',
        name: 'schedule-preview',
        component: require('./screens/schedule/preview')
    },

    {
        path: '/schedule',
        name: 'schedule',
        component: require('./screens/schedule/index')
    },

    {
        path: '/redis/:id',
        name: 'redis-preview',
        component: require('./screens/redis/preview')
    },

    {
        path: '/redis',
        name: 'redis',
        component: require('./screens/redis/index')
    },

    {
        path: '/monitored-tags',
        name: 'monitored-tags',
        component: require('./screens/monitoring/index')
    },
];
