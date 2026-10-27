import { describe, expect, it } from 'vitest';
import { resolveBasePath } from './base';

describe('resolveBasePath', () => {
    it('uses the path when no subdirectory is set', () => {
        expect(resolveBasePath('telescope', null)).toBe('/telescope');
        expect(resolveBasePath('telescope', '')).toBe('/telescope');
        expect(resolveBasePath('telescope', undefined)).toBe('/telescope');
    });

    it('prefixes the path with the subdirectory', () => {
        expect(resolveBasePath('telescope', 'app')).toBe('/app/telescope');
        expect(resolveBasePath('telescope', 'apps/admin')).toBe('/apps/admin/telescope');
    });

    it('trims surrounding slashes from each segment', () => {
        expect(resolveBasePath('/telescope/', '/app/')).toBe('/app/telescope');
    });

    it('resolves to the subdirectory when the path is empty', () => {
        expect(resolveBasePath('', 'app')).toBe('/app');
        expect(resolveBasePath('/', 'app')).toBe('/app');
    });

    it('resolves to an empty string when both segments are empty', () => {
        expect(resolveBasePath('', null)).toBe('');
        expect(resolveBasePath('/', '')).toBe('');
        expect(resolveBasePath(null, null)).toBe('');
    });
});
