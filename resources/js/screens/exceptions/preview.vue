<script type="text/ecmascript-6">
    import axios from 'axios';

    export default {
        components: {
            'code-preview': require('./../../components/ExceptionCodePreview').default,
            'stack-trace': require('./../../components/Stacktrace').default
        },

        data(){
            return {
                entry: null,
                batch: [],
                currentTab: 'message'
            };
        },

        methods: {
            hasContext() {
                return this.entry.content.hasOwnProperty('context')
                    && this.entry.content.context !== null;
            },

            markExceptionAsResolved(entry) {
                this.alertConfirm('Are you sure you want to mark this exception as resolved?', () => {

                    axios.put(Telescope.basePath + '/telescope-api/exceptions/' + entry.id, {
                        'resolved_at': 'now',
                    }).then(response => {
                        this.entry = response.data.entry;
                    })
                });
            },
        }
    }
</script>

<template>
    <preview-screen title="Exception Details" resource="exceptions" :id="$route.params.id">
        <template slot="table-parameters" slot-scope="slotProps">
            <tr>
                <td class="table-fit text-muted">Type</td>
                <td>
                    {{slotProps.entry.content.class}}
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Location</td>
                <td>
                    {{slotProps.entry.content.file}}:{{slotProps.entry.content.line}}
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Occurrences</td>
                <td>
                    <router-link :to="{name:'exceptions', query: {family_hash: slotProps.entry.family_hash}}" class="control-action">
                        View Other Occurrences
                    </router-link>
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Resolved at</td>

                <td>
                    <span v-if="entry.content.resolved_at">
                        {{localTime(entry.content.resolved_at)}} ({{timeAgo(entry.content.resolved_at)}})
                    </span>
                    <span v-if="!entry.content.resolved_at">
                        <button class="btn btn-sm btn-success" v-on:click.prevent="markExceptionAsResolved(entry)">Mark as resolved</button>
                    </span>
                </td>
            </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps" class="mt-5">
            <div class="card mt-5 overflow-hidden">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link" :class="{active: currentTab=='message'}" href="#" v-on:click.prevent="currentTab='message'">Message</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" :class="{active: currentTab=='location'}" href="#" v-on:click.prevent="currentTab='location'">Location</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" :class="{active: currentTab=='context'}" href="#" v-show="hasContext()" v-on:click.prevent="currentTab='context'">Context</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" :class="{active: currentTab=='trace'}" href="#" v-on:click.prevent="currentTab='trace'">Stacktrace</a>
                    </li>
                </ul>

                <div>
                    <pre class="code-bg p-4 mb-0 text-white" v-show="currentTab=='message'">{{slotProps.entry.content.message}}</pre>

                    <code-preview
                            v-show="currentTab=='location'"
                            :lines="slotProps.entry.content.line_preview"
                            :highlighted-line="slotProps.entry.content.line">
                    </code-preview>

                    <div class="code-bg p-4 mb-0 text-white" v-show="currentTab=='context'">
                        <copy-clipboard :data="slotProps.entry.content.context">
                            <vue-json-pretty :data="slotProps.entry.content.context"></vue-json-pretty>
                        </copy-clipboard>
                    </div>

                    <stack-trace :trace="slotProps.entry.content.trace" v-show="currentTab=='trace'"></stack-trace>
                </div>
            </div>
        </div>
    </preview-screen>
</template>

<style scoped>

</style>
