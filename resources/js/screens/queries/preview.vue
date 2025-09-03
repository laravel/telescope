<script type="text/ecmascript-6">
import hljs from 'highlight.js/lib/core';
import sql from 'highlight.js/lib/languages/sql';
import { format, supportedDialects } from 'sql-formatter';

hljs.registerLanguage('sql', sql);

export default {
    methods: {
        highlightSQL() {
            this.$nextTick(() => {
                hljs.highlightElement(this.$refs.sqlcode);
            });
        },
        formatSql(sql, driver) {
            let formatterConfig = {};

            if (driver) {
                if (driver === 'pgsql') {
                    driver = 'postgresql';
                }

                if (driver === 'sqlsrv') {
                    driver = 'transactsql';
                }

                if (supportedDialects.includes(driver)) {
                    formatterConfig = {language: driver};
                }
            }

            return format(sql, formatterConfig);
        }
    }
}
</script>

<template>
    <preview-screen title="Query Details" resource="queries" :id="$route.params.id" v-on:ready="highlightSQL()">
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
                    <copy-clipboard :data="formatSql(slotProps.entry.content.sql, slotProps.entry.content.driver)">
                        <pre class="code-bg text-white" ref="sqlcode">{{ formatSql(slotProps.entry.content.sql, slotProps.entry.content.driver) }}</pre>
                    </copy-clipboard>
                </div>
            </div>
        </div>
    </preview-screen>
</template>
