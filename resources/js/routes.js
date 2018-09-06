export default [
    {path: '/', redirect: '/overview'},

    {
        path: '/overview',
        name: 'overview',
        component: {template: '<div>Here we show a few metrics like in Horizon.</div>'}
    },

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
        path: '/log/:id',
        name: 'log-preview',
        component: require('./screens/log/preview')
    },

    {
        path: '/log',
        name: 'log',
        component: require('./screens/log/index')
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
        path: '/queue/:id',
        name: 'queue-preview',
        component: require('./screens/queue/preview')
    },

    {
        path: '/queue',
        name: 'queue',
        component: require('./screens/queue/index')
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
        path: '/artisan/:id',
        name: 'artisan-preview',
        component: require('./screens/artisan/preview')
    },

    {
        path: '/artisan',
        name: 'artisan',
        component: require('./screens/artisan/index')
    },
];
