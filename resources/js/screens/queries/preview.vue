<script type="text/ecmascript-6">
    import _ from 'lodash';
    import sqlFormatter from "sql-formatter";
    import hljs from 'highlight.js/lib/highlight';
    import sql from 'highlight.js/lib/languages/sql';

    export default {
        components: {
            'stack-trace': require('./../../components/Stacktrace')
        },

        data() {
            return {
                entry: null,
                batch: [],
                currentTab: 'query',
            };
        },

        computed: {
            showSource() {
                return this.entry.content.source && this.entry.content.source.length;
            }
        },

        methods: {
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

            <tr v-if="showSource">
                <td class="table-fit font-weight-bold">Location</td>
                <td>
                    {{slotProps.entry.content.source[0].file}}:{{slotProps.entry.content.source[0].line}}
                </td>
            </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps">
            <div class="card mt-5">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link" :class="{active: currentTab=='query'}" href="#" v-on:click.prevent="currentTab='query'">Query</a>
                    </li>
                    <li class="nav-item" v-if="showSource">
                        <a class="nav-link" :class="{active: currentTab=='source'}" href="#" v-on:click.prevent="currentTab='source'">Source</a>
                    </li>
                </ul>

                <div>
                    <pre class="code-bg p-4 mb-0 text-white" ref="sqlcode" v-show="currentTab=='query'">{{formatSQL(slotProps.entry.content.sql, slotProps.entry.content.bindings)}}</pre>

                    <stack-trace
                            v-if="showSource"
                            v-show="currentTab=='source'"
                            :trace="slotProps.entry.content.source">
                    </stack-trace>
                </div>
            </div>
        </div>
    </preview-screen>
</template>
