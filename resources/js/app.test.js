import axios from 'axios';
import { afterEach, describe, expect, it, vi } from 'vitest';

vi.mock('vue', () => import('vue/dist/vue.esm.js'));

vi.mock('axios', () => ({
    default: {
        defaults: { headers: { common: {} } },
        post: vi.fn(),
        delete: vi.fn(() => new Promise(() => {})),
    },
}));

let app;

afterEach(() => {
    app?.$destroy();
    app = null;
    document.body.innerHTML = '';
    window.history.replaceState({}, '', '/');
    vi.clearAllMocks();
    vi.unstubAllGlobals();
});

describe('dashboard base path', () => {
    it.each([
        ['telescope', undefined, '/telescope'],
        ['telescope', '', '/telescope'],
        ['telescope', 'app', '/app/telescope'],
        ['/debug/', '/apps/admin/', '/apps/admin/debug'],
        ['', 'app', '/app'],
        ['/', '/app/', '/app'],
        ['', '', ''],
        ['/', '/', ''],
    ])('shares the route and API prefix for path %s and subdirectory %s', async (path, subdirectory, expected) => {
        vi.resetModules();
        vi.stubGlobal('Telescope', { path, subdirectory, timezone: 'UTC', recording: true });
        vi.stubGlobal('localStorage', {});
        window.history.replaceState({}, '', expected + '/requests');
        document.body.innerHTML = '<div id="telescope"></div>';

        await import('./app');

        app = document.getElementById('telescope').__vue__;

        expect(window.Telescope.basePath).toBe(expected);
        expect(app.$router.resolve('/mail/entry-id').href).toBe(expected + '/mail/entry-id');
        expect(app.$route.path).toBe('/requests');

        app.toggleRecording();
        app.clearEntries(false);

        expect(axios.post).toHaveBeenCalledWith(expected + '/telescope-api/toggle-recording');
        expect(axios.delete).toHaveBeenCalledWith(expected + '/telescope-api/entries');
    });
});
