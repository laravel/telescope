<script type="text/ecmascript-6">
import hljs from 'highlight.js/lib/core';
import json from 'highlight.js/lib/languages/json';
import sql from 'highlight.js/lib/languages/sql';
import { format, supportedDialects } from 'sql-formatter';

hljs.registerLanguage('sql', sql);
hljs.registerLanguage('json', json);

const driverAliases = {
    pgsql: 'postgresql',
    sqlsrv: 'transactsql',
    mongodb: 'json',
};

export default {
    methods: {
        highlightQuery() {
            this.$nextTick(() => {
                hljs.highlightElement(this.$refs.querycode);
            });
        },

        /**
         * Format the recorded query for display. SQL queries are formatted with
         * the matching dialect. Other drivers, MongoDB being one, record their
         * commands as JSON, which is pretty printed.
         */
        formatQuery(query, driver) {
            driver = driverAliases[driver] || driver;

            if (driver === 'json') {
                return JSON.stringify(JSON.parse(query), null, 2);
            }

            const dialect = this.sqlDialect(driver);

            if (dialect) {
                return format(query, { language: dialect });
            }

            return query;
        },

        /**
         * Resolve the SQL dialect matching the given connection driver.
         * Returns null when the driver does not speak SQL, such as MongoDB.
         */
        sqlDialect(driver) {
            if (!driver) {
                return 'sql';
            }

            return supportedDialects.includes(driver) ? driver : null;
        },

        /**
         * Highlight language used by the query preview.
         */
        queryLanguage(driver) {
            return (driverAliases[driver] || driver) === 'json' ? 'json' : 'sql';
        }
    }
}
</script>

<template>
    <preview-screen title="Query Details" resource="queries" :id="$route.params.id" v-on:ready="highlightQuery()">
        <template slot="table-parameters" slot-scope="slotProps">
            <tr>
                <td class="table-fit text-muted">Connection</td>
                <td>
                    {{ slotProps.entry.content.connection }}
                </td>
            </tr>

            <tr v-if="slotProps.entry.content.file">
                <td class="table-fit text-muted">Location</td>
                <td>{{ slotProps.entry.content.file }}:{{ slotProps.entry.content.line }}</td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Duration</td>
                <td>
                    <span class="badge badge-danger" v-if="slotProps.entry.content.slow">
                        {{ slotProps.entry.content.time }}ms
                    </span>

                    <span v-else> {{ slotProps.entry.content.time }}ms </span>
                </td>
            </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps">
            <div class="card mt-5 overflow-hidden">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link active">Query</a>
                    </li>
                </ul>
                <div class="code-bg p-4 mb-0 text-white">
                    <copy-clipboard :data="formatQuery(slotProps.entry.content.sql, slotProps.entry.content.driver)">
                        <pre class="code-bg text-white"
                            ref="querycode"
                            :class="'language-' + queryLanguage(slotProps.entry.content.driver)"
                        >{{ formatQuery(slotProps.entry.content.sql, slotProps.entry.content.driver) }}</pre>
                    </copy-clipboard>
                </div>
            </div>
        </div>
    </preview-screen>
</template>
