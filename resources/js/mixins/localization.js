import forEach from 'lodash/forEach';

export default {
    methods: {
        /**
         * Translate the given key.
         */
        __: (key, replace) => {
            let translation = window.Telescope.translations[key] || key;

            forEach(replace, (value, key) => {
                key = new String(key);

                if (value === null) {
                    console.error(`Translation '${translation}' for key '${key}' contains a null replacement.`);

                    return;
                }

                value = new String(value);

                const keyUpper = key.toUpperCase();
                const keyTitle = key.charAt(0).toUpperCase() + key.slice(1);

                const searches = [':' + key, ':' + keyUpper, ':' + keyTitle];

                const valueUpper = value.toUpperCase();
                const valueTitle = value.charAt(0).toUpperCase() + value.slice(1);

                const replacements = [value, valueUpper, valueTitle];

                for (let i = searches.length - 1; i >= 0; i--) {
                    translation = translation.replace(searches[i], replacements[i]);
                }
            });

            return translation;
        },
    },
};
