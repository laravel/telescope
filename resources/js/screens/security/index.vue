<script type="text/ecmascript-6">
import $ from 'jquery';
import _ from 'lodash';
import axios from 'axios';
import StylesMixin from './../../mixins/entriesStyles';
import Base from './../../base';
import PatternForm from './pattern-form.vue';
import GlobalRuleForm from './global-rule-form.vue';

export default {
    components: {
        PatternForm,
        GlobalRuleForm,
    },

    mixins: [
        Base,
        StylesMixin,
    ],

    data() {
        return {
            whitelistPatterns: [],
            whitelistReady: false,
            editingPattern: null,
            showPatternForm: false,
            globalSecurityRules: [],
            globalRulesReady: false,
            editingGlobalRule: null,
            showGlobalRuleForm: false,
            activeSection: 'whitelist',
        };
    },

    mounted() {
        this.loadWhitelist();
        this.loadGlobalSecurityRules();
    },

    methods: {
        loadWhitelist() {
            axios.get(Telescope.basePath + '/telescope-api/security-whitelist').then(response => {
                this.whitelistPatterns = response.data.patterns;
                this.whitelistReady = true;
            }).catch(() => {
                this.whitelistReady = true;
            });
        },

        removePattern(pattern) {
            this.alertConfirm('Are you sure you want to remove this whitelist pattern?', () => {
                axios.delete(Telescope.basePath + '/telescope-api/security-whitelist/' + pattern.id).then(() => {
                    this.loadWhitelist();
                });
            });
        },

        openNewPatternModal() {
            this.editingPattern = null;
            this.showPatternForm = true;
            this.$nextTick(() => {
                $('#patternFormModal').modal({
                    backdrop: 'static',
                });
                // Reset form component
                if (this.$refs.patternForm) {
                    this.$refs.patternForm.loadPattern();
                }
            });
        },

        editPattern(pattern) {
            // Deep clone the pattern to avoid reactivity issues
            // Ensure all rules are properly parsed from JSON strings if needed
            const clonedPattern = JSON.parse(JSON.stringify(pattern));

            // Ensure rules are objects, not strings
            ['query_rules', 'payload_rules', 'header_rules', 'path_params_rules'].forEach(ruleKey => {
                if (clonedPattern[ruleKey]) {
                    if (typeof clonedPattern[ruleKey] === 'string') {
                        try {
                            clonedPattern[ruleKey] = JSON.parse(clonedPattern[ruleKey]);
                        } catch (e) {
                            clonedPattern[ruleKey] = {};
                        }
                    }
                    // Ensure it's an object
                    if (typeof clonedPattern[ruleKey] !== 'object' || Array.isArray(clonedPattern[ruleKey])) {
                        clonedPattern[ruleKey] = {};
                    }
                } else {
                    clonedPattern[ruleKey] = {};
                }
            });

            this.editingPattern = clonedPattern;
            this.showPatternForm = true;
            this.$nextTick(() => {
                $('#patternFormModal').modal({
                    backdrop: 'static',
                });
                // Force reload pattern in form component
                if (this.$refs.patternForm) {
                    this.$refs.patternForm.loadPattern();
                }
            });
        },

        savePattern(formData) {
            // Validate required fields
            if (!formData.path_pattern || !formData.path_pattern.trim()) {
                this.alertError('Path pattern is required');
                return;
            }

            const url = this.editingPattern
                ? Telescope.basePath + '/telescope-api/security-whitelist/' + this.editingPattern.id
                : Telescope.basePath + '/telescope-api/security-whitelist';

            const method = this.editingPattern ? 'put' : 'post';

            // Ensure all fields are present, even if empty
            const dataToSend = {
                name: formData.name || null,
                path_pattern: formData.path_pattern.trim(),
                method: formData.method || null,
                is_regex: formData.is_regex || false,
                enabled: formData.enabled !== undefined ? formData.enabled : true,
                path_params_rules: formData.path_params_rules || {},
                query_rules: formData.query_rules || {},
                payload_rules: formData.payload_rules || {},
                header_rules: formData.header_rules || {},
                header_rules: formData.header_rules || {},
            };

            axios[method](url, dataToSend).then(() => {
                this.loadWhitelist();
                $('#patternFormModal').modal('hide');
                this.showPatternForm = false;
                this.editingPattern = null;
            }).catch(error => {
                const errorMessage = error.response?.data?.message ||
                                   (error.response?.data?.errors ? JSON.stringify(error.response.data.errors) : 'Failed to save pattern');
                this.alertError(errorMessage);
            });
        },

        cancelPatternForm() {
            $('#patternFormModal').modal('hide');
            this.showPatternForm = false;
            this.editingPattern = null;
            // Reset form
            if (this.$refs.patternForm) {
                this.$refs.patternForm.loadPattern();
            }
        },

        loadGlobalSecurityRules() {
            axios.get(Telescope.basePath + '/telescope-api/global-security-rules').then(response => {
                this.globalSecurityRules = response.data.rules || [];
                this.globalRulesReady = true;

                // If defaults available and no rules, show initialize button
                if (response.data.defaults_available && this.globalSecurityRules.length === 0) {
                    // Will show initialize button in UI
                }
            }).catch(() => {
                this.globalRulesReady = true;
            });
        },

        initializeDefaults() {
            axios.post(Telescope.basePath + '/telescope-api/global-security-rules/initialize').then(() => {
                this.loadGlobalSecurityRules();
                this.alertSuccess('Default security rules initialized successfully');
            }).catch(error => {
                this.alertError(error.response?.data?.message || 'Failed to initialize defaults');
            });
        },

        removeGlobalRule(rule) {
            this.alertConfirm('Are you sure you want to remove this global security rule?', () => {
                axios.delete(Telescope.basePath + '/telescope-api/global-security-rules/' + rule.id).then(() => {
                    this.loadGlobalSecurityRules();
                });
            });
        },

        openNewGlobalRuleModal() {
            this.editingGlobalRule = null;
            this.showGlobalRuleForm = true;
            this.$nextTick(() => {
                $('#globalRuleFormModal').modal({
                    backdrop: 'static',
                });
            });
        },

        editGlobalRule(rule) {
            this.editingGlobalRule = JSON.parse(JSON.stringify(rule));
            this.showGlobalRuleForm = true;
            this.$nextTick(() => {
                $('#globalRuleFormModal').modal({
                    backdrop: 'static',
                });
            });
        },

        saveGlobalRule(formData) {
            const url = this.editingGlobalRule
                ? Telescope.basePath + '/telescope-api/global-security-rules/' + this.editingGlobalRule.id
                : Telescope.basePath + '/telescope-api/global-security-rules';

            const method = this.editingGlobalRule ? 'put' : 'post';

            axios[method](url, formData).then(() => {
                this.loadGlobalSecurityRules();
                $('#globalRuleFormModal').modal('hide');
                this.showGlobalRuleForm = false;
                this.editingGlobalRule = null;
            }).catch(error => {
                this.alertError(error.response?.data?.message || 'Failed to save global security rule');
            });
        },

        cancelGlobalRuleForm() {
            $('#globalRuleFormModal').modal('hide');
            this.showGlobalRuleForm = false;
            this.editingGlobalRule = null;
        },

        truncate(str, length) {
            if (!str) return '';
            return str.length > length ? str.substring(0, length) + '...' : str;
        },
    },
}
</script>

<template>
    <div>
        <!-- Tabs for Whitelist and Global Security -->
        <ul class="nav nav-pills mb-3">
            <li class="nav-item">
                <a class="nav-link" :class="{ active: activeSection === 'whitelist' }" href="#" @click.prevent="activeSection = 'whitelist'">Whitelist</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" :class="{ active: activeSection === 'global' }" href="#" @click.prevent="activeSection = 'global'">Global Security Rules</a>
            </li>
        </ul>

        <!-- Whitelist Section -->
        <div v-if="activeSection === 'whitelist'" class="card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0">Security Whitelist</h2>
                <button class="btn btn-primary" v-on:click.prevent="openNewPatternModal">Add Pattern</button>
            </div>

            <div v-if="!whitelistReady" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin mr-2 fill-text-color">
                    <path
                        d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"
                    ></path>
                </svg>
                <span>Loading...</span>
            </div>

            <div
                v-if="whitelistReady && whitelistPatterns.length == 0"
                class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius"
            >
                <span>No whitelist patterns configured. Add patterns to allow endpoints.</span>
            </div>

            <table v-if="whitelistReady && whitelistPatterns.length > 0" class="table table-hover mb-0">
                <thead>
                    <th>Name</th>
                    <th>Path Pattern</th>
                    <th>Method</th>
                    <th>Type</th>
                    <th>Rules</th>
                    <th>Status</th>
                    <th></th>
                </thead>

                <tbody>
                    <tr v-for="pattern in whitelistPatterns" :key="pattern.id">
                        <td>{{ pattern.name || '-' }}</td>
                        <td>
                            <code>{{ pattern.path_pattern }}</code>
                            <span v-if="pattern.is_regex" class="badge badge-info ml-1">Regex</span>
                        </td>
                        <td>{{ pattern.method || 'Any' }}</td>
                        <td>
                            <span v-if="Object.keys(pattern.query_rules || {}).length > 0" class="badge badge-secondary mr-1">Query</span>
                            <span v-if="Object.keys(pattern.payload_rules || {}).length > 0" class="badge badge-secondary mr-1">Payload</span>
                            <span v-if="Object.keys(pattern.header_rules || {}).length > 0" class="badge badge-secondary mr-1">Headers</span>
                            <span v-if="Object.keys(pattern.query_rules || {}).length === 0 && Object.keys(pattern.payload_rules || {}).length === 0 && Object.keys(pattern.header_rules || {}).length === 0" class="text-muted">None</span>
                        </td>
                        <td>
                            <span class="badge" :class="pattern.enabled ? 'badge-success' : 'badge-secondary'">
                                {{ pattern.enabled ? 'Enabled' : 'Disabled' }}
                            </span>
                        </td>
                        <td class="table-fit">
                            <a href="#" class="control-action mr-2" v-on:click.prevent="editPattern(pattern)" title="Edit">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="16" height="16">
                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                                </svg>
                            </a>
                            <a href="#" class="control-action" v-on:click.prevent="removePattern(pattern)" title="Delete">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path
                                        d="M6 2l2-2h4l2 2h4v2H2V2h4zM3 6h14l-1 14H4L3 6zm5 2v10h1V8H8zm3 0v10h1V8h-1z"
                                    ></path>
                                </svg>
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div
                class="modal"
                id="patternFormModal"
                tabindex="-1"
                role="dialog"
                aria-labelledby="patternFormModalLabel"
                aria-hidden="true"
                style="display: none;"
            >
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            {{ editingPattern ? 'Edit' : 'Add' }} Whitelist Pattern
                        </div>

                        <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                            <pattern-form
                                ref="patternForm"
                                :key="editingPattern ? 'edit-' + editingPattern.id : 'new'"
                                :pattern="editingPattern"
                            ></pattern-form>
                        </div>

                        <div class="modal-footer justify-content-start flex-row-reverse">
                            <button class="btn btn-primary" @click="savePattern($refs.patternForm ? $refs.patternForm.getFormData() : {})">Save</button>
                            <button class="btn" @click="cancelPatternForm">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <index-screen title="Security" resource="security">
        <tr slot="table-header">
            <th scope="col">Method</th>
            <th scope="col">Path</th>
            <th scope="col" class="text-center">Status</th>
            <th scope="col">IP Address</th>
            <th scope="col">Suspicious Reasons</th>
            <th scope="col">Happened</th>
            <th scope="col"></th>
        </tr>

        <template slot="row" slot-scope="slotProps">
            <td class="table-fit pr-0">
                <span class="badge" :class="'badge-' + requestMethodClass(slotProps.entry.content.method)">
                    {{ slotProps.entry.content.method }}
                </span>
            </td>

            <td :title="slotProps.entry.content.uri">
                {{ truncate(slotProps.entry.content.path || slotProps.entry.content.uri, 50) }}
            </td>

            <td class="table-fit text-center">
                <span class="badge badge-danger">
                    Suspicious
                </span>
            </td>

            <td class="table-fit text-muted">
                {{ slotProps.entry.content.ip_address || '-' }}
            </td>

            <td>
                <span
                    v-for="(reason, index) in slotProps.entry.content.suspicious_reasons"
                    :key="index"
                    class="badge badge-warning mr-1"
                >
                    {{ reason }}
                </span>
            </td>

            <td
                class="table-fit text-muted"
                :data-timeago="slotProps.entry.created_at"
                :title="slotProps.entry.created_at"
            >
                {{ timeAgo(slotProps.entry.created_at) }}
            </td>

            <td class="table-fit">
                <router-link
                    :to="{
                        name: 'security-preview',
                        params: { id: slotProps.entry.id },
                    }"
                    class="control-action"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <path
                            fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM6.75 9.25a.75.75 0 000 1.5h4.59l-2.1 1.95a.75.75 0 001.02 1.1l3.5-3.25a.75.75 0 000-1.1l-3.5-3.25a.75.75 0 10-1.02 1.1l2.1 1.95H6.75z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </router-link>
            </td>
        </template>
        </index-screen>

        <!-- Global Security Rules Section -->
        <div v-if="activeSection === 'global'" class="card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0">Global Security Rules</h2>
                <div>
                    <button v-if="globalSecurityRules.length === 0" class="btn btn-success mr-2" @click="initializeDefaults">Initialize Defaults</button>
                    <button class="btn btn-primary" v-on:click.prevent="openNewGlobalRuleModal">Add Rule</button>
                </div>
            </div>

            <div v-if="!globalRulesReady" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin mr-2 fill-text-color">
                    <path
                        d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"
                    ></path>
                </svg>
                <span>Loading...</span>
            </div>

            <div
                v-if="globalRulesReady && globalSecurityRules.length == 0"
                class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius"
            >
                <div class="text-center">
                    <p>No global security rules configured.</p>
                    <button class="btn btn-success" @click="initializeDefaults">Initialize Default Security Rules</button>
                </div>
            </div>

            <table v-if="globalRulesReady && globalSecurityRules.length > 0" class="table table-hover mb-0">
                <thead>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Patterns</th>
                    <th>Excluded Paths</th>
                    <th>Status</th>
                    <th></th>
                </thead>

                <tbody>
                    <tr v-for="rule in globalSecurityRules" :key="rule.id">
                        <td>
                            <strong>{{ rule.name }}</strong>
                            <div v-if="rule.description" class="text-muted small">{{ rule.description }}</div>
                        </td>
                        <td>
                            <span class="badge badge-info">{{ rule.category }}</span>
                        </td>
                        <td>
                            <span v-for="(pattern, index) in rule.patterns.slice(0, 3)" :key="index" class="badge badge-secondary mr-1">
                                {{ truncate(pattern, 20) }}
                            </span>
                            <span v-if="rule.patterns.length > 3" class="text-muted">+{{ rule.patterns.length - 3 }} more</span>
                        </td>
                        <td>
                            <span v-if="rule.exclude_paths && rule.exclude_paths.length > 0">
                                <span v-for="(path, index) in rule.exclude_paths" :key="index" class="badge badge-warning mr-1">
                                    {{ truncate(path, 15) }}
                                </span>
                            </span>
                            <span v-else class="text-muted">None</span>
                        </td>
                        <td>
                            <span class="badge" :class="rule.enabled ? 'badge-success' : 'badge-secondary'">
                                {{ rule.enabled ? 'Enabled' : 'Disabled' }}
                            </span>
                        </td>
                        <td class="table-fit">
                            <a href="#" class="control-action mr-2" v-on:click.prevent="editGlobalRule(rule)" title="Edit">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="16" height="16">
                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                                </svg>
                            </a>
                            <a href="#" class="control-action" v-on:click.prevent="removeGlobalRule(rule)" title="Delete">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path
                                        d="M6 2l2-2h4l2 2h4v2H2V2h4zM3 6h14l-1 14H4L3 6zm5 2v10h1V8H8zm3 0v10h1V8h-1z"
                                    ></path>
                                </svg>
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Global Rule Form Modal -->
            <div
                class="modal"
                id="globalRuleFormModal"
                tabindex="-1"
                role="dialog"
                aria-labelledby="globalRuleFormModalLabel"
                aria-hidden="true"
            >
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            {{ editingGlobalRule ? 'Edit' : 'Add' }} Global Security Rule
                        </div>

                        <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                            <global-rule-form
                                ref="globalRuleForm"
                                :key="editingGlobalRule ? 'edit-' + editingGlobalRule.id : 'new'"
                                :rule="editingGlobalRule"
                            ></global-rule-form>
                        </div>

                        <div class="modal-footer justify-content-start flex-row-reverse">
                            <button class="btn btn-primary" @click="saveGlobalRule($refs.globalRuleForm ? $refs.globalRuleForm.getFormData() : {})">Save</button>
                            <button class="btn" @click="cancelGlobalRuleForm">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

