<script type="text/ecmascript-6">
    import _ from 'lodash';
    import sqlFormatter from "sql-formatter";
    import hljs from 'highlight.js/lib/highlight';
    import sql from 'highlight.js/lib/languages/sql';

    export default {
        data(){
            return {
                entry: null,
                batch: [],
            };
        },

        methods:{
            formatSQL(sql, params) {
                return sqlFormatter.format(sql, {
                    params: _.map(params, param => _.isString(param) ? '"'+param+'"' : param)
                });
            },

            highlightSQL() {
                this.$nextTick(() => {
                    hljs.registerLanguage('sql', sql);
                    hljs.highlightBlock(this.$refs.sqlcode);
                });
            }
        }
    }
</script>

<template>
    <preview-screen title="Query Details" resource="queries" :id="$route.params.id" v-on:ready="highlightSQL()">
        <template slot="table-parameters" slot-scope="slotProps">
            <tr>
                <td class="table-fit font-weight-bold">Connection</td>
                <td>
                    {{slotProps.entry.content.connection}}
                </td>
            </tr>

            <tr  v-if="slotProps.entry.content.file">
                <td class="table-fit font-weight-bold">Location</td>
                <td>
                    {{slotProps.entry.content.file}}:{{slotProps.entry.content.line}}
                </td>
            </tr>

            <tr>
                <td class="table-fit font-weight-bold">Duration</td>
                <td>
                    <span class="badge badge-danger font-weight-light" v-if="slotProps.entry.content.slow">
                        {{slotProps.entry.content.time}}ms
                    </span>

                    <span v-else>
                        {{slotProps.entry.content.time}}ms
                    </span>
                </td>
            </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps">
            <div class="card mt-5">
                <div class="card-header"><h5>Query</h5></div>

                <pre class="code-bg p-4 mb-0 text-white" ref="sqlcode">{{formatSQL(slotProps.entry.content.sql, slotProps.entry.content.bindings)}}</pre>
            </div>
        </div>
    </preview-screen>
</template>
