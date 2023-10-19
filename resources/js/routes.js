import MailPreview from './screens/mail/preview.vue';
import MailIndex from './screens/mail/index.vue';
import ExceptionsPreview from './screens/exceptions/preview.vue';
import ExceptionsIndex from './screens/exceptions/index.vue';
import DumpsIndex from './screens/dumps/index.vue';
import LogsPreview from './screens/logs/preview.vue';
import LogsIndex from './screens/logs/index.vue';
import NotificationsPreview from './screens/notifications/preview.vue';
import NotificationsIndex from './screens/notifications/index.vue';
import JobsPreview from './screens/jobs/preview.vue';
import JobsIndex from './screens/jobs/index.vue';
import BatchesPreview from './screens/batches/preview.vue';
import BatchesIndex from './screens/batches/index.vue';
import EventsPreview from './screens/events/preview.vue';
import EventsIndex from './screens/events/index.vue';
import CachePreview from './screens/cache/preview.vue';
import CacheIndex from './screens/cache/index.vue';
import QueriesPreview from './screens/queries/preview.vue';
import QueriesIndex from './screens/queries/index.vue';
import ModelsPreview from './screens/models/preview.vue';
import ModelsIndex from './screens/models/index.vue';
import RequestsPreview from './screens/requests/preview.vue';
import RequestsIndex from './screens/requests/index.vue';
import CommandsPreview from './screens/commands/preview.vue';
import CommandsIndex from './screens/commands/index.vue';
import SchedulePreview from './screens/schedule/preview.vue';
import ScheduleIndex from './screens/schedule/index.vue';
import RedisPreview from './screens/redis/preview.vue';
import RedisIndex from './screens/redis/index.vue';
import MonitoringIndex from './screens/monitoring/index.vue';
import GatesPreview from './screens/gates/preview.vue';
import GatesIndex from './screens/gates/index.vue';
import ViewsPreview from './screens/views/preview.vue';
import ViewsIndex from './screens/views/index.vue';
import ClientRequestsPreview from './screens/client-requests/preview.vue';
import ClientRequestsIndex from './screens/client-requests/index.vue';

export default [
    { path: '/', redirect: '/requests' },

    {
        path: '/mail/:id',
        name: 'mail-preview',
        component: MailPreview,
    },

    {
        path: '/mail',
        name: 'mail',
        component: MailIndex,
    },

    {
        path: '/exceptions/:id',
        name: 'exception-preview',
        component: ExceptionsPreview,
    },

    {
        path: '/exceptions',
        name: 'exceptions',
        component: ExceptionsIndex,
    },

    {
        path: '/dumps',
        name: 'dumps',
        component: DumpsIndex,
    },

    {
        path: '/logs/:id',
        name: 'log-preview',
        component: LogsPreview,
    },

    {
        path: '/logs',
        name: 'logs',
        component: LogsIndex,
    },

    {
        path: '/notifications/:id',
        name: 'notification-preview',
        component: NotificationsPreview,
    },

    {
        path: '/notifications',
        name: 'notifications',
        component: NotificationsIndex,
    },

    {
        path: '/jobs/:id',
        name: 'job-preview',
        component: JobsPreview,
    },

    {
        path: '/jobs',
        name: 'jobs',
        component: JobsIndex,
    },

    {
        path: '/batches/:id',
        name: 'batch-preview',
        component: BatchesPreview,
    },

    {
        path: '/batches',
        name: 'batches',
        component: BatchesIndex,
    },

    {
        path: '/events/:id',
        name: 'event-preview',
        component: EventsPreview,
    },

    {
        path: '/events',
        name: 'events',
        component: EventsIndex,
    },

    {
        path: '/cache/:id',
        name: 'cache-preview',
        component: CachePreview,
    },

    {
        path: '/cache',
        name: 'cache',
        component: CacheIndex,
    },

    {
        path: '/queries/:id',
        name: 'query-preview',
        component: QueriesPreview,
    },

    {
        path: '/queries',
        name: 'queries',
        component: QueriesIndex,
    },

    {
        path: '/models/:id',
        name: 'model-preview',
        component: ModelsPreview,
    },

    {
        path: '/models',
        name: 'models',
        component: ModelsIndex,
    },

    {
        path: '/requests/:id',
        name: 'request-preview',
        component: RequestsPreview,
    },

    {
        path: '/requests',
        name: 'requests',
        component: RequestsIndex,
    },

    {
        path: '/commands/:id',
        name: 'command-preview',
        component: CommandsPreview,
    },

    {
        path: '/commands',
        name: 'commands',
        component: CommandsIndex,
    },

    {
        path: '/schedule/:id',
        name: 'schedule-preview',
        component: SchedulePreview,
    },

    {
        path: '/schedule',
        name: 'schedule',
        component: ScheduleIndex,
    },

    {
        path: '/redis/:id',
        name: 'redis-preview',
        component: RedisPreview,
    },

    {
        path: '/redis',
        name: 'redis',
        component: RedisIndex,
    },

    {
        path: '/monitored-tags',
        name: 'monitored-tags',
        component: MonitoringIndex,
    },

    {
        path: '/gates/:id',
        name: 'gate-preview',
        component: GatesPreview,
    },

    {
        path: '/gates',
        name: 'gates',
        component: GatesIndex,
    },

    {
        path: '/views/:id',
        name: 'view-preview',
        component: ViewsPreview,
    },

    {
        path: '/views',
        name: 'views',
        component: ViewsIndex,
    },

    {
        path: '/client-requests/:id',
        name: 'client-request-preview',
        component: ClientRequestsPreview,
    },

    {
        path: '/client-requests',
        name: 'client-requests',
        component: ClientRequestsIndex,
    },
];
