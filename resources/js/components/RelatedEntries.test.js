import { flushPromises, mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import { createRouter, createWebHistory } from 'vue-router';
import Base from '../base';
import routes from '../routes';
import RelatedEntries from './RelatedEntries.vue';

const batch = [
    { id: 'log-id', type: 'log', content: { level: 'info', message: 'Logged' }, tags: [] },
    { id: 'query-id', type: 'query', content: { sql: 'select 1', time: '1.00', connection: 'sqlite', hash: 'a' }, tags: [] },
];

function mountRelatedEntries(router) {
    return mount(RelatedEntries, {
        props: { entry: { id: 'request-id', content: {} }, batch },
        global: { plugins: [router], mixins: [Base] },
    });
}

function tab(wrapper, title) {
    return wrapper.findAll('.nav-pills a').find((link) => link.text().startsWith(title));
}

describe('related entries', () => {
    it('restores the selected tab when going back to the entry', async () => {
        const router = createRouter({ history: createWebHistory('/telescope/'), routes });

        await router.push('/requests/request-id');

        const wrapper = mountRelatedEntries(router);
        await flushPromises();

        expect(tab(wrapper, 'Logs').classes()).toContain('active');

        await tab(wrapper, 'Queries').trigger('click');
        await flushPromises();

        expect(window.location.hash).toBe('#queries');

        await router.push('/queries/query-id');

        const returned = new Promise((resolve) => window.addEventListener('popstate', resolve, { once: true }));
        window.history.back();
        await returned;
        await flushPromises();

        expect(window.location.pathname + window.location.hash).toBe('/telescope/requests/request-id#queries');

        const remounted = mountRelatedEntries(router);
        await flushPromises();

        expect(tab(remounted, 'Queries').classes()).toContain('active');
    });
});
