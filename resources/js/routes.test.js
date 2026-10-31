import { mount } from '@vue/test-utils';
import { readFileSync } from 'node:fs';
import { describe, expect, it } from 'vitest';
import { createMemoryHistory, createRouter } from 'vue-router';
import routes from './routes';

/**
 * The sidebar links of the dashboard layout, e.g. "/requests".
 */
const sidebar = [
    ...readFileSync('resources/views/layout.blade.php', 'utf8').matchAll(
        /<router-link active-class="active" to="([^"]+)"/g
    ),
].map((match) => match[1]);

function makeRouter() {
    return createRouter({ history: createMemoryHistory(), routes });
}

/**
 * Render the sidebar links while the router shows the given path.
 */
async function activeLinksAt(path) {
    const router = makeRouter();

    await router.push(path);

    const wrapper = mount(
        {
            template: `<div>
                <router-link v-for="link in sidebar" :key="link" :to="link" active-class="active">{{ link }}</router-link>
            </div>`,
            data: () => ({ sidebar }),
        },
        { global: { plugins: [router] } }
    );

    return wrapper.findAll('a.active').map((link) => link.text());
}

describe('routes', () => {
    it('reads the sidebar links from the layout', () => {
        expect(sidebar).toContain('/requests');
        expect(sidebar.length).toBeGreaterThan(15);
    });

    it('marks only the matching sidebar link active on a list screen', async () => {
        for (const link of sidebar) {
            expect(await activeLinksAt(link)).toEqual([link]);
        }
    });

    it('keeps the list link active on a preview screen', async () => {
        const router = makeRouter();
        const withPreview = sidebar.filter((link) => router.resolve(`${link}/entry-id`).matched.length);

        expect(withPreview).toContain('/requests');

        for (const link of withPreview) {
            expect(await activeLinksAt(`${link}/entry-id`)).toEqual([link]);
        }
    });

    it('resolves the named list and preview routes', () => {
        const router = makeRouter();

        expect(router.resolve({ name: 'requests', query: { tag: 'a' } }).fullPath).toBe('/requests?tag=a');
        expect(router.resolve({ name: 'request-preview', params: { id: 'abc' } }).path).toBe('/requests/abc');
        expect(router.resolve({ name: 'dumps' }).path).toBe('/dumps');
    });
});
