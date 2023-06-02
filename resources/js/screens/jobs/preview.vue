<script type="text/ecmascript-6">
    import axios from 'axios';
    import StylesMixin from './../../mixins/entriesStyles';

    export default {
        components: {
            'code-preview': require('./../../components/ExceptionCodePreview').default,
            'stack-trace': require('./../../components/Stacktrace').default
        },


        mixins: [
            StylesMixin,
        ],


        data() {
            return {
                entry: null,
                batch: [],
                currentTab: 'data'
            };
        }
    }
</script>

<template>
    <preview-screen title="Job Details" resource="jobs" :id="$route.params.id" entry-point="true">
        <template slot="table-parameters" slot-scope="slotProps">
            <tr>
                <td class="table-fit text-muted">Status</td>
                <td>
                    <span class="badge" :class="'badge-'+jobStatusClass(slotProps.entry.content.status)">
                        {{slotProps.entry.content.status}}
                    </span>
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Job</td>
                <td>
                    {{slotProps.entry.content.name}}
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Connection</td>
                <td>
                    {{slotProps.entry.content.connection}}
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Queue</td>
                <td>
                    {{slotProps.entry.content.queue}}
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Tries</td>
                <td>
                    {{slotProps.entry.content.tries || '-'}}
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Timeout</td>
                <td>
                    {{slotProps.entry.content.timeout || '-'}}
                </td>
            </tr>

            <tr v-if="slotProps.entry.content.data.batchId">
                <td class="table-fit text-muted">Batch</td>
                <td>
                    <router-link :to="{name:'batch-preview', params:{id: slotProps.entry.content.data.batchId}}" class="control-action">
                        {{slotProps.entry.content.data.batchId}}
                    </router-link>
                </td>
            </tr>

        </template>

        <div slot="after-attributes-card" slot-scope="slotProps">
            <div class="card mt-5">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link" :class="{active: currentTab=='data'}" href="#" v-on:click.prevent="currentTab='data'">Data</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" :class="{active: currentTab=='exception'}" href="#" v-on:click.prevent="currentTab='exception'" v-if="slotProps.entry.content.exception">Exception Message</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" :class="{active: currentTab=='preview'}" href="#" v-on:click.prevent="currentTab='preview'" v-if="slotProps.entry.content.exception">Exception Location</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" :class="{active: currentTab=='trace'}" href="#" v-on:click.prevent="currentTab='trace'" v-if="slotProps.entry.content.exception">Stacktrace</a>
                    </li>
                </ul>
                <div>
                    <div class="code-bg p-4 mb-0 text-white" v-show="currentTab=='data'">
                        <copy-clipboard :data="slotProps.entry.content.data">
                            <vue-json-pretty :data="slotProps.entry.content.data"></vue-json-pretty>
                        </copy-clipboard>
                    </div>
                    <pre class="code-bg p-4 mb-0 text-white" v-if="slotProps.entry.content.exception" v-show="currentTab=='exception'">{{slotProps.entry.content.exception.message}}</pre>
                    <stack-trace :trace="slotProps.entry.content.exception.trace" v-if="slotProps.entry.content.exception" v-show="currentTab=='trace'"></stack-trace>
                    <code-preview
                            v-if="slotProps.entry.content.exception"
                            v-show="currentTab=='preview'"
                            :lines="slotProps.entry.content.exception.line_preview"
                            :highlighted-line="slotProps.entry.content.exception.line">
                    </code-preview>
                </div>
            </div>


            <!-- Additional Information -->
            <related-entries :entry="entry" :batch="batch">
            </related-entries>
        </div>
    </preview-screen>
</template>

<style scoped>

</style>
