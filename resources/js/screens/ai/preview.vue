<script type="text/ecmascript-6">
import StylesMixin from './../../mixins/entriesStyles';

export default {
    mixins: [
        StylesMixin,
    ],

    data() {
        return {
            entry: null,
            batch: [],
            currentPayloadTab: 'prompt',
        };
    },

    methods: {
        statusLabel(status) {
            return status ? status.replace(/_/g, ' ') : 'unknown';
        },

        optional(value) {
            return value === undefined || value === null || value === '' ? '-' : value;
        },

        hasItems(items) {
            return Array.isArray(items) && items.length > 0;
        },

        items(value) {
            return Array.isArray(value) ? value : [];
        },

        duration(content) {
            if (content.duration) {
                return content.duration;
            }

            if (!Array.isArray(content.steps)) {
                return null;
            }

            let duration = this.items(content.steps).reduce((total, step) => total + (Number(step.duration) || 0), 0);

            return duration || null;
        },

        tokens(content) {
            if (!content.usage) {
                return null;
            }

            let total = Object.keys(content.usage).reduce((tokens, key) => {
                return tokens + (Number(content.usage[key]) || 0);
            }, 0);

            return total || null;
        },

        captured(value) {
            return value !== undefined && value !== null;
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
                        {{ statusLabel(slotProps.entry.content.status) }}
                    </span>
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Invocation ID</td>
                <td>{{ slotProps.entry.content.invocation_id }}</td>
            </tr>

            <tr v-if="slotProps.entry.content.parent_invocation_id">
                <td class="table-fit text-muted">Parent Invocation ID</td>
                <td>{{ slotProps.entry.content.parent_invocation_id }}</td>
            </tr>

            <tr v-if="slotProps.entry.content.parent_tool_invocation_id">
                <td class="table-fit text-muted">Parent Tool Invocation ID</td>
                <td>{{ slotProps.entry.content.parent_tool_invocation_id }}</td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Streaming</td>
                <td>{{ slotProps.entry.content.streaming ? 'Yes' : 'No' }}</td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Agent</td>
                <td>{{ optional(slotProps.entry.content.agent) }}</td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Provider</td>
                <td>{{ optional(slotProps.entry.content.provider) }}</td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Model</td>
                <td>{{ optional(slotProps.entry.content.model) }}</td>
            </tr>

            <tr v-if="slotProps.entry.content.timeout">
                <td class="table-fit text-muted">Timeout</td>
                <td>{{ slotProps.entry.content.timeout }}</td>
            </tr>

            <tr v-if="slotProps.entry.content.finish_reason">
                <td class="table-fit text-muted">Finish Reason</td>
                <td>{{ slotProps.entry.content.finish_reason }}</td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Activity</td>
                <td>
                    {{ slotProps.entry.content.step_count || 0 }} steps,
                    {{ slotProps.entry.content.tool_count || 0 }} tools,
                    {{ slotProps.entry.content.failover_count || 0 }} failovers,
                    {{ slotProps.entry.content.approval_count || 0 }} approvals
                </td>
            </tr>

            <tr v-if="slotProps.entry.content.pending_approval_count">
                <td class="table-fit text-muted">Pending Approvals</td>
                <td>{{ slotProps.entry.content.pending_approval_count }}</td>
            </tr>

            <tr v-if="duration(slotProps.entry.content)">
                <td class="table-fit text-muted">Duration</td>
                <td>{{ duration(slotProps.entry.content) }}ms</td>
            </tr>

            <tr v-if="tokens(slotProps.entry.content)">
                <td class="table-fit text-muted">Tokens</td>
                <td>{{ tokens(slotProps.entry.content) }}</td>
            </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps">
            <div class="card mt-5 overflow-hidden">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            :class="{ active: currentPayloadTab == 'prompt' }"
                            href="#"
                            v-on:click.prevent="currentPayloadTab = 'prompt'"
                            >Prompt</a
                        >
                    </li>
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            :class="{ active: currentPayloadTab == 'response' }"
                            href="#"
                            v-on:click.prevent="currentPayloadTab = 'response'"
                            >Response</a
                        >
                    </li>
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            :class="{ active: currentPayloadTab == 'usage' }"
                            href="#"
                            v-on:click.prevent="currentPayloadTab = 'usage'"
                            >Usage</a
                        >
                    </li>
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            :class="{ active: currentPayloadTab == 'meta' }"
                            href="#"
                            v-on:click.prevent="currentPayloadTab = 'meta'"
                            >Meta</a
                        >
                    </li>
                </ul>

                <div class="code-bg p-4 mb-0 text-white" v-show="currentPayloadTab == 'prompt'">
                    <template v-if="captured(slotProps.entry.content.prompt)">
                        <copy-clipboard :data="slotProps.entry.content.prompt">
                            <vue-json-pretty :data="slotProps.entry.content.prompt"></vue-json-pretty>
                        </copy-clipboard>
                    </template>
                    <span v-else>Prompt content was not captured.</span>
                </div>

                <div class="code-bg p-4 mb-0 text-white" v-show="currentPayloadTab == 'response'">
                    <template
                        v-if="slotProps.entry.content.response && (
                            captured(slotProps.entry.content.response.text)
                            || captured(slotProps.entry.content.response.messages)
                            || captured(slotProps.entry.content.response.raw)
                        )"
                    >
                        <copy-clipboard :data="slotProps.entry.content.response">
                            <vue-json-pretty :data="slotProps.entry.content.response"></vue-json-pretty>
                        </copy-clipboard>
                    </template>
                    <span v-else-if="slotProps.entry.content.response">Response content was not captured.</span>
                    <span v-else>Response has not been recorded.</span>
                </div>

                <div class="code-bg p-4 mb-0 text-white" v-show="currentPayloadTab == 'usage'">
                    <template v-if="slotProps.entry.content.usage">
                        <copy-clipboard :data="slotProps.entry.content.usage">
                            <vue-json-pretty :data="slotProps.entry.content.usage"></vue-json-pretty>
                        </copy-clipboard>
                    </template>
                    <span v-else>Usage was not captured.</span>
                </div>

                <div class="code-bg p-4 mb-0 text-white" v-show="currentPayloadTab == 'meta'">
                    <template v-if="slotProps.entry.content.response && slotProps.entry.content.response.meta">
                        <copy-clipboard :data="slotProps.entry.content.response.meta">
                            <vue-json-pretty :data="slotProps.entry.content.response.meta"></vue-json-pretty>
                        </copy-clipboard>
                    </template>
                    <span v-else>Response metadata was not captured.</span>
                </div>
            </div>

            <div class="card mt-5 overflow-hidden" v-if="slotProps.entry.content.response || slotProps.entry.content.usage || slotProps.entry.content.exception">
                <div class="card-header d-flex align-items-center">
                    <h5 class="mb-0">Run Result</h5>
                </div>

                <div class="card-bg-secondary ai-detail-list">
                    <div class="ai-detail-block" v-if="slotProps.entry.content.usage">
                        <div class="ai-detail-label text-muted">Usage</div>
                        <div class="code-bg p-4 mb-0 text-white">
                            <copy-clipboard :data="slotProps.entry.content.usage">
                                <vue-json-pretty :data="slotProps.entry.content.usage"></vue-json-pretty>
                            </copy-clipboard>
                        </div>
                    </div>

                    <div class="ai-detail-block" v-if="slotProps.entry.content.response">
                        <div class="ai-detail-label text-muted">Response Summary</div>
                        <div class="code-bg p-4 mb-0 text-white">
                            <copy-clipboard :data="slotProps.entry.content.response">
                                <vue-json-pretty :data="slotProps.entry.content.response"></vue-json-pretty>
                            </copy-clipboard>
                        </div>
                    </div>

                    <div class="ai-detail-block" v-if="slotProps.entry.content.exception">
                        <div class="ai-detail-label text-muted">Exception</div>
                        <div class="code-bg p-4 mb-0 text-white">
                            <copy-clipboard :data="slotProps.entry.content.exception">
                                <vue-json-pretty :data="slotProps.entry.content.exception"></vue-json-pretty>
                            </copy-clipboard>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-5 overflow-hidden">
                <div class="card-header d-flex align-items-center">
                    <h5 class="mb-0">Steps</h5>
                </div>

                <table class="table table-hover mb-0" v-if="hasItems(slotProps.entry.content.steps)">
                    <thead>
                        <tr>
                            <th scope="col">Step</th>
                            <th scope="col">Provider</th>
                            <th scope="col">Model</th>
                            <th scope="col">Status</th>
                            <th scope="col">Final</th>
                            <th scope="col" class="text-right">Duration</th>
                            <th scope="col">Finish</th>
                            <th scope="col" class="text-right">Tools</th>
                            <th scope="col" class="text-right">Pending</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="step in slotProps.entry.content.steps" :key="step.step_number">
                            <td class="table-fit">{{ step.step_number }}</td>
                            <td class="table-fit">{{ optional(step.provider) }}</td>
                            <td class="table-fit">{{ optional(step.model) }}</td>
                            <td class="table-fit">{{ optional(step.status) }}</td>
                            <td class="table-fit">{{ step.final ? 'Yes' : 'No' }}</td>
                            <td class="table-fit text-right text-muted">{{ step.duration ? step.duration + 'ms' : '-' }}</td>
                            <td class="table-fit">{{ optional(step.finish_reason) }}</td>
                            <td class="table-fit text-right text-muted">{{ step.tool_call_count || 0 }}</td>
                            <td class="table-fit text-right text-muted">{{ step.pending_approval_count || 0 }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="card-bg-secondary p-4 text-muted" v-else>No provider steps recorded.</div>
            </div>

            <div class="card mt-5 overflow-hidden">
                <div class="card-header d-flex align-items-center">
                    <h5 class="mb-0">Tools</h5>
                </div>

                <table class="table table-hover mb-0" v-if="hasItems(slotProps.entry.content.tools)">
                    <thead>
                        <tr>
                            <th scope="col">Tool</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-right">Duration</th>
                            <th scope="col">Class</th>
                        </tr>
                    </thead>

                    <tbody>
                        <template v-for="tool in slotProps.entry.content.tools">
                            <tr :key="tool.id">
                                <td>
                                    {{ optional(tool.tool || tool.id) }}
                                    <small class="text-muted d-block" v-if="tool.id">{{ tool.id }}</small>
                                </td>
                                <td class="table-fit">{{ optional(tool.status) }}</td>
                                <td class="table-fit text-right text-muted">{{ tool.duration ? tool.duration + 'ms' : '-' }}</td>
                                <td class="table-fit" :title="tool.tool_class">{{ truncate(tool.tool_class || '-', 45) }}</td>
                            </tr>

                            <tr v-if="captured(tool.arguments)" :key="tool.id + '-arguments'" class="ai-detail-row">
                                <td colspan="4">
                                    <div class="ai-inline-detail">
                                        <div class="ai-detail-label text-muted">Arguments</div>
                                        <div class="code-bg p-4 mb-0 text-white">
                                            <copy-clipboard :data="tool.arguments">
                                                <vue-json-pretty :data="tool.arguments"></vue-json-pretty>
                                            </copy-clipboard>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="captured(tool.result)" :key="tool.id + '-result'" class="ai-detail-row">
                                <td colspan="4">
                                    <div class="ai-inline-detail">
                                        <div class="ai-detail-label text-muted">Result</div>
                                        <div class="code-bg p-4 mb-0 text-white">
                                            <copy-clipboard :data="tool.result">
                                                <vue-json-pretty :data="tool.result"></vue-json-pretty>
                                            </copy-clipboard>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="tool.exception" :key="tool.id + '-exception'" class="ai-detail-row">
                                <td colspan="4">
                                    <div class="ai-inline-detail">
                                        <div class="ai-detail-label text-muted">Exception</div>
                                        <div class="code-bg p-4 mb-0 text-white">
                                            <copy-clipboard :data="tool.exception">
                                                <vue-json-pretty :data="tool.exception"></vue-json-pretty>
                                            </copy-clipboard>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                <div class="card-bg-secondary p-4 text-muted" v-else>No tools recorded.</div>
            </div>

            <div class="card mt-5 overflow-hidden">
                <div class="card-header d-flex align-items-center">
                    <h5 class="mb-0">Failovers</h5>
                </div>

                <table class="table table-hover mb-0" v-if="hasItems(slotProps.entry.content.failovers)">
                    <thead>
                        <tr>
                            <th scope="col">Provider</th>
                            <th scope="col">Model</th>
                            <th scope="col">Agent</th>
                            <th scope="col">Status</th>
                            <th scope="col">Exception</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="(failover, index) in slotProps.entry.content.failovers" :key="index">
                            <td class="table-fit">{{ optional(failover.provider) }}</td>
                            <td class="table-fit">{{ optional(failover.model) }}</td>
                            <td class="table-fit" :title="failover.agent">{{ truncate(failover.agent || '-', 45) }}</td>
                            <td class="table-fit">{{ optional(failover.status) }}</td>
                            <td :title="failover.exception ? failover.exception.class : ''">
                                {{ failover.exception ? truncate(failover.exception.class, 70) : '-' }}
                                <small class="text-muted d-block" v-if="failover.exception && failover.exception.message">
                                    {{ truncate(failover.exception.message, 120) }}
                                </small>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="card-bg-secondary p-4 text-muted" v-else>No failovers recorded.</div>
            </div>

            <div class="card mt-5 overflow-hidden">
                <div class="card-header d-flex align-items-center">
                    <h5 class="mb-0">Approvals</h5>
                </div>

                <table class="table table-hover mb-0" v-if="hasItems(slotProps.entry.content.approvals)">
                    <thead>
                        <tr>
                            <th scope="col">Status</th>
                            <th scope="col">Agent</th>
                            <th scope="col">Conversation</th>
                            <th scope="col">User</th>
                            <th scope="col" class="text-right">Pending</th>
                            <th scope="col" class="text-right">Results</th>
                        </tr>
                    </thead>

                    <tbody>
                        <template v-for="(approval, index) in slotProps.entry.content.approvals">
                            <tr :key="index">
                                <td class="table-fit">{{ optional(approval.status) }}</td>
                                <td class="table-fit" :title="approval.agent">{{ truncate(approval.agent || '-', 45) }}</td>
                                <td :title="approval.conversation_id">{{ truncate(approval.conversation_id || '-', 60) }}</td>
                                <td class="table-fit">
                                    {{ approval.conversation_user ? optional(approval.conversation_user.id) : '-' }}
                                </td>
                                <td class="table-fit text-right text-muted">{{ approval.pending_approval_count || 0 }}</td>
                                <td class="table-fit text-right text-muted">{{ approval.tool_result_count || 0 }}</td>
                            </tr>

                            <tr v-if="hasItems(approval.pending_approvals)" :key="index + '-pending'" class="ai-detail-row">
                                <td colspan="6">
                                    <div class="ai-inline-detail">
                                        <div class="ai-detail-label text-muted">Pending Approvals</div>
                                        <div class="code-bg p-4 mb-0 text-white">
                                            <copy-clipboard :data="approval.pending_approvals">
                                                <vue-json-pretty :data="approval.pending_approvals"></vue-json-pretty>
                                            </copy-clipboard>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="hasItems(approval.tool_results)" :key="index + '-results'" class="ai-detail-row">
                                <td colspan="6">
                                    <div class="ai-inline-detail">
                                        <div class="ai-detail-label text-muted">Tool Results</div>
                                        <div class="code-bg p-4 mb-0 text-white">
                                            <copy-clipboard :data="approval.tool_results">
                                                <vue-json-pretty :data="approval.tool_results"></vue-json-pretty>
                                            </copy-clipboard>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                <div class="card-bg-secondary p-4 text-muted" v-else>No approval checkpoints recorded.</div>
            </div>

        </div>
    </preview-screen>
</template>

<style scoped>
.ai-detail-list {
    padding: 1rem 1.25rem;
}

.ai-detail-block + .ai-detail-block {
    margin-top: 1rem;
}

.ai-detail-label {
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
}

.ai-detail-row > td {
    padding-top: 0;
}

.ai-inline-detail {
    padding: 0.25rem 0 0.5rem;
}
</style>
