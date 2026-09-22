<script type="text/ecmascript-6">
import StylesMixin from './../../mixins/entriesStyles';
import ExceptionCodePreview from './../../components/ExceptionCodePreview.vue';

export default {
    components: {
        'code-preview': ExceptionCodePreview,
    },

    mixins: [
        StylesMixin,
    ],

    data() {
        return {
            entry: null,
            batch: [],
            currentTab: 'steps',
        };
    },

    methods: {
        optional(value) {
            return value === undefined || value === null || value === '' ? '-' : value;
        },

        items(value) {
            return Array.isArray(value) ? value : [];
        },

        tokens(usage) {
            if (! usage) {
                return null;
            }

            return (Number(usage.prompt_tokens) || 0) + (Number(usage.completion_tokens) || 0);
        },
    },
}
</script>

<template>
    <preview-screen title="AI Details" resource="ai" :id="$route.params.id">
        <template slot="table-parameters" slot-scope="slotProps">
            <tr>
                <td class="table-fit text-muted">Status</td>
                <td>
                    <span class="badge" :class="'badge-' + aiStatusClass(slotProps.entry.content.status)">
                        {{ aiStatusLabel(slotProps.entry.content.status) }}
                    </span>
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Agent</td>
                <td>{{ optional(slotProps.entry.content.agent) }}</td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Provider</td>
                <td>{{ optional(slotProps.entry.content.provider) }} / {{ optional(slotProps.entry.content.model) }}</td>
            </tr>

            <tr v-if="slotProps.entry.content.finish_reason">
                <td class="table-fit text-muted">Finish Reason</td>
                <td>{{ slotProps.entry.content.finish_reason }}</td>
            </tr>

            <tr v-if="slotProps.entry.content.duration">
                <td class="table-fit text-muted">Duration</td>
                <td>{{ Math.round(slotProps.entry.content.duration) }}ms</td>
            </tr>

            <tr v-if="tokens(slotProps.entry.content.usage) !== null">
                <td class="table-fit text-muted">Tokens</td>
                <td>
                    {{ tokens(slotProps.entry.content.usage) }}
                    <small class="text-muted">
                        ({{ slotProps.entry.content.usage.prompt_tokens }} prompt,
                        {{ slotProps.entry.content.usage.completion_tokens }} completion)
                    </small>
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Streaming</td>
                <td>{{ slotProps.entry.content.streaming ? 'Yes' : 'No' }}</td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Invocation</td>
                <td>{{ slotProps.entry.content.invocation_id }}</td>
            </tr>

            <tr v-if="slotProps.entry.content.parent_invocation_id">
                <td class="table-fit text-muted">Parent Invocation</td>
                <td>
                    <router-link
                        :to="{ name: 'ai-preview', params: { id: slotProps.entry.content.parent_invocation_id } }"
                        class="control-action"
                    >
                        {{ slotProps.entry.content.parent_invocation_id }}
                    </router-link>
                </td>
            </tr>

            <tr v-if="slotProps.entry.content.conversation_id">
                <td class="table-fit text-muted">Conversation</td>
                <td>{{ slotProps.entry.content.conversation_id }}</td>
            </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps">
            <div class="card mt-5">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link" :class="{active: currentTab == 'steps'}" href="#"
                           v-on:click.prevent="currentTab = 'steps'">Steps</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" :class="{active: currentTab == 'tools'}" href="#"
                           v-on:click.prevent="currentTab = 'tools'">Tools</a>
                    </li>
                    <li class="nav-item" v-if="items(slotProps.entry.content.failovers).length">
                        <a class="nav-link" :class="{active: currentTab == 'failovers'}" href="#"
                           v-on:click.prevent="currentTab = 'failovers'">Failovers</a>
                    </li>
                    <li class="nav-item" v-if="items(slotProps.entry.content.pending_approvals).length">
                        <a class="nav-link" :class="{active: currentTab == 'approvals'}" href="#"
                           v-on:click.prevent="currentTab = 'approvals'">Approvals</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" :class="{active: currentTab == 'content'}" href="#"
                           v-on:click.prevent="currentTab = 'content'">Prompt &amp; Response</a>
                    </li>
                    <li class="nav-item" v-if="slotProps.entry.content.exception">
                        <a class="nav-link" :class="{active: currentTab == 'exception'}" href="#"
                           v-on:click.prevent="currentTab = 'exception'">Exception</a>
                    </li>
                </ul>

                <!-- Steps -->
                <div v-show="currentTab == 'steps'">
                    <table class="table table-hover mb-0" v-if="items(slotProps.entry.content.steps).length">
                        <thead>
                            <tr>
                                <th scope="col">Step</th>
                                <th scope="col">Status</th>
                                <th scope="col">Provider</th>
                                <th scope="col">Finish Reason</th>
                                <th class="text-right">Tool Calls</th>
                                <th class="text-right">Tokens</th>
                                <th class="text-right">Duration</th>
                            </tr>
                        </thead>

                        <tbody>
                            <template v-for="step in slotProps.entry.content.steps">
                                <tr :key="step.step">
                                    <td class="table-fit">{{ step.step }}</td>
                                    <td class="table-fit">
                                        <span class="badge" :class="'badge-' + aiStatusClass(step.status)">
                                            {{ aiStatusLabel(step.status) }}
                                        </span>
                                    </td>
                                    <td>{{ optional(step.provider) }} / {{ optional(step.model) }}</td>
                                    <td class="table-fit text-muted">{{ optional(step.finish_reason) }}</td>
                                    <td class="table-fit text-right text-muted">{{ step.tool_calls || 0 }}</td>
                                    <td class="table-fit text-right text-muted">{{ optional(tokens(step.usage)) }}</td>
                                    <td class="table-fit text-right text-muted">
                                        {{ step.duration ? Math.round(step.duration) + 'ms' : '-' }}
                                    </td>
                                </tr>
                                <tr v-if="step.text" :key="step.step + '-text'">
                                    <td colspan="7" class="code-bg p-4 text-white">
                                        <copy-clipboard :data="step.text">
                                            <vue-json-pretty :data="step.text"></vue-json-pretty>
                                        </copy-clipboard>
                                    </td>
                                </tr>
                                <tr v-if="step.exception" :key="step.step + '-exception'">
                                    <td colspan="7" class="code-bg p-4 text-white">
                                        {{ step.exception.class }}: {{ step.exception.message }}
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>

                    <div class="card-bg-secondary p-4 text-muted" v-else>No provider steps recorded.</div>
                </div>

                <!-- Tools -->
                <div v-show="currentTab == 'tools'">
                    <table class="table table-hover mb-0" v-if="items(slotProps.entry.content.tools).length">
                        <thead>
                            <tr>
                                <th scope="col">Tool</th>
                                <th scope="col">Status</th>
                                <th class="text-right">Duration</th>
                            </tr>
                        </thead>

                        <tbody>
                            <template v-for="tool in slotProps.entry.content.tools">
                                <tr :key="tool.id">
                                    <td>
                                        {{ optional(tool.tool) }}<br />
                                        <small class="text-muted">{{ tool.tool_class }}</small>
                                    </td>
                                    <td class="table-fit">
                                        <span class="badge" :class="'badge-' + aiStatusClass(tool.status)">
                                            {{ aiStatusLabel(tool.status) }}
                                        </span>
                                    </td>
                                    <td class="table-fit text-right text-muted">
                                        {{ tool.duration ? Math.round(tool.duration) + 'ms' : '-' }}
                                    </td>
                                </tr>
                                <tr v-if="tool.arguments !== null && tool.arguments !== undefined" :key="tool.id + '-arguments'">
                                    <td colspan="3" class="code-bg p-4 text-white">
                                        <copy-clipboard :data="tool.arguments">
                                            <vue-json-pretty :data="tool.arguments"></vue-json-pretty>
                                        </copy-clipboard>
                                    </td>
                                </tr>
                                <tr v-if="tool.result !== null && tool.result !== undefined" :key="tool.id + '-result'">
                                    <td colspan="3" class="code-bg p-4 text-white">
                                        <copy-clipboard :data="tool.result">
                                            <vue-json-pretty :data="tool.result"></vue-json-pretty>
                                        </copy-clipboard>
                                    </td>
                                </tr>
                                <tr v-if="tool.exception" :key="tool.id + '-exception'">
                                    <td colspan="3" class="code-bg p-4 text-white">
                                        {{ tool.exception.class }}: {{ tool.exception.message }}
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>

                    <div class="card-bg-secondary p-4 text-muted" v-else>No tools were invoked.</div>
                </div>

                <!-- Failovers -->
                <div v-show="currentTab == 'failovers'">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Provider</th>
                                <th scope="col">Reason</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="(failover, index) in items(slotProps.entry.content.failovers)" :key="index">
                                <td>{{ optional(failover.provider) }} / {{ optional(failover.model) }}</td>
                                <td class="text-muted">
                                    {{ failover.exception.class }}: {{ failover.exception.message }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Approvals -->
                <div v-show="currentTab == 'approvals'">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Tool</th>
                                <th scope="col">Reason</th>
                            </tr>
                        </thead>

                        <tbody>
                            <template v-for="approval in items(slotProps.entry.content.pending_approvals)">
                                <tr :key="approval.id">
                                    <td>{{ optional(approval.tool) }}</td>
                                    <td class="text-muted">{{ optional(approval.reason) }}</td>
                                </tr>
                                <tr v-if="approval.arguments !== null && approval.arguments !== undefined" :key="approval.id + '-arguments'">
                                    <td colspan="2" class="code-bg p-4 text-white">
                                        <copy-clipboard :data="approval.arguments">
                                            <vue-json-pretty :data="approval.arguments"></vue-json-pretty>
                                        </copy-clipboard>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Prompt & Response -->
                <div v-show="currentTab == 'content'">
                    <div
                        class="card-bg-secondary p-4 text-muted"
                        v-if="slotProps.entry.content.prompt === null && slotProps.entry.content.response === null"
                    >
                        Prompt and response content is not recorded. Enable the <code>content</code> option of the AI
                        watcher to record it.
                    </div>

                    <div v-else>
                        <div class="code-bg p-4 text-white border-bottom">
                            <copy-clipboard :data="slotProps.entry.content.prompt">
                                <vue-json-pretty :data="slotProps.entry.content.prompt"></vue-json-pretty>
                            </copy-clipboard>
                        </div>

                        <div class="code-bg p-4 text-white">
                            <copy-clipboard :data="slotProps.entry.content.response">
                                <vue-json-pretty :data="slotProps.entry.content.response"></vue-json-pretty>
                            </copy-clipboard>
                        </div>
                    </div>
                </div>

                <!-- Exception -->
                <div v-show="currentTab == 'exception'" v-if="slotProps.entry.content.exception">
                    <pre class="code-bg p-4 mb-0 text-white">{{ slotProps.entry.content.exception.class }}: {{ slotProps.entry.content.exception.message }}</pre>

                    <code-preview
                        v-if="slotProps.entry.content.exception.line_preview"
                        :lines="slotProps.entry.content.exception.line_preview"
                        :highlighted-line="slotProps.entry.content.exception.line"
                    >
                    </code-preview>
                </div>
            </div>

            <related-entries :entry="entry" :batch="batch"></related-entries>
        </div>
    </preview-screen>
</template>

<style scoped></style>
