<script type="text/ecmascript-6">
import { format } from 'sql-formatter';

    export default {
        methods: {
            formatSql(sql, language) {
                return format(sql, { language: this.mapLanguage(language)});
            },

            mapLanguage(language) {
                if (language === 'sql') {
                    return language;
                }

                switch (language) {
                    case 'pgsql':
                        return 'postgresql';
                    case 'mysql':
                        return 'mysql';
                    case 'mariadb':
                        return 'mariadb'
                    case 'sqlite':
                        return 'sqlite'
                    case 'sqlsrv':
                        return 'transactsql'
                }
            },

            mapHighlightingLanguage(language) {
                if (language === 'sql') {
                    return language;
                } else if(language === 'sqlsrv') {
                    return 'tsql';
                }

                return 'pgsql';
            }
        }
    }
</script>

<template>
    <preview-screen title="Query Details" resource="queries" :id="$route.params.id">
        <template slot="table-parameters" slot-scope="slotProps">
            <tr>
                <td class="table-fit text-muted">Connection</td>
                <td>
                    {{slotProps.entry.content.connection}}
                </td>
            </tr>

            <tr  v-if="slotProps.entry.content.file">
                <td class="table-fit text-muted">Location</td>
                <td>
                    {{slotProps.entry.content.file}}:{{slotProps.entry.content.line}}
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Duration</td>
                <td>
                    <span class="badge badge-danger" v-if="slotProps.entry.content.slow">
                        {{slotProps.entry.content.time}}ms
                    </span>

                    <span v-else>
                        {{slotProps.entry.content.time}}ms
                    </span>
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
                    <copy-clipboard  :data="formatSql(slotProps.entry.content.sql, slotProps?.entry?.content?.driver ?? 'sql')">
                        <higlighter :code="formatSql(slotProps.entry.content.sql, slotProps.entry.content?.driver ?? 'sql')"
                                    :language="mapHighlightingLanguage(slotProps.entry.content?.driver ?? 'sql')"
                                    class="code-bg text-white class" />
                    </copy-clipboard>
                </div>
            </div>
        </div>
    </preview-screen>
</template>
