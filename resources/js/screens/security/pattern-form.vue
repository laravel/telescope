<script type="text/ecmascript-6">
import $ from 'jquery';

export default {
    props: {
        pattern: {
            type: Object,
            default: null,
        },
    },

    data() {
        return {
            formData: {
                name: '',
                path_pattern: '',
                method: '',
                is_regex: false,
                enabled: true,
                path_params_rules: {},
                query_rules: {},
                payload_rules: {},
                header_rules: {}, // Must be initialized as object, not array
            },
            activeTab: 'basic',
            currentRuleContext: '',
            currentRuleKey: '',
            forbiddenPatternInputs: {},
        };
    },

    mounted() {
        this.loadPattern();
    },

    watch: {
        pattern: {
            handler(newVal) {
                if (newVal) {
                    this.loadPattern();
                }
            },
            immediate: true,
            deep: true,
        },
        'formData.path_pattern': {
            handler(newPattern) {
                // Auto-detect path parameters from pattern
                this.autoDetectPathParameters(newPattern);
            },
        },
        'formData.header_rules': {
            handler(newVal, oldVal) {
                console.log('header_rules changed:', {
                    newVal: newVal,
                    oldVal: oldVal,
                    keys: Object.keys(newVal || {})
                });
            },
            deep: true,
            immediate: true
        }
    },

    methods: {
        // Helper to map context to correct rules key
        getRulesKey(context) {
            const contextToRulesKey = {
                'path_params': 'path_params_rules',
                'query': 'query_rules',
                'payload': 'payload_rules',
                'headers': 'header_rules'  // FIX: 'headers' -> 'header_rules' (not 'headers_rules')
            };
            return contextToRulesKey[context] || `${context}_rules`;
        },
        loadPattern() {
            if (this.pattern && Object.keys(this.pattern).length > 0) {
                // Helper function to safely parse rules
                const parseRules = (rules) => {
                    if (!rules) {
                        return {};
                    }
                    // If it's already an object, clone it
                    if (typeof rules === 'object' && !Array.isArray(rules)) {
                        return JSON.parse(JSON.stringify(rules));
                    }
                    // If it's a string, parse it
                    if (typeof rules === 'string') {
                        try {
                            const parsed = JSON.parse(rules);
                            return typeof parsed === 'object' && !Array.isArray(parsed) ? parsed : {};
                        } catch (e) {
                            return {};
                        }
                    }
                    return {};
                };

                this.formData = {
                    name: this.pattern.name || '',
                    path_pattern: this.pattern.path_pattern || '',
                    method: this.pattern.method || '',
                    is_regex: !!this.pattern.is_regex,
                    enabled: this.pattern.enabled !== undefined ? !!this.pattern.enabled : true,
                    path_params_rules: parseRules(this.pattern.path_params_rules),
                    query_rules: parseRules(this.pattern.query_rules),
                    payload_rules: parseRules(this.pattern.payload_rules),
                    header_rules: parseRules(this.pattern.header_rules),
                };
                
            } else {
                this.formData = {
                    name: '',
                    path_pattern: '',
                    method: '',
                    is_regex: false,
                    enabled: true,
                    path_params_rules: {},
                    query_rules: {},
                    payload_rules: {},
                    header_rules: {},
                };
            }
            this.activeTab = 'basic';
        },

        getFormData() {
            return this.formData;
        },

        /**
         * Auto-detect path parameters from pattern and create rules for them.
         */
        autoDetectPathParameters(pattern) {
            if (!pattern) {
                return;
            }

            // Extract parameter names from pattern (e.g., {id}, {slug})
            const paramMatches = pattern.match(/\{([^}]+)\}/g);
            
            if (!paramMatches || paramMatches.length === 0) {
                // No parameters in pattern, clear path_params_rules
                if (Object.keys(this.formData.path_params_rules).length > 0) {
                    // Only clear if we're not editing an existing pattern
                    if (!this.pattern || !this.pattern.id) {
                        this.formData.path_params_rules = {};
                    }
                }
                return;
            }

            // Extract parameter names
            const detectedParams = paramMatches.map(match => match.replace(/[{}]/g, ''));

            // Create rules for newly detected parameters (don't overwrite existing)
            detectedParams.forEach(paramName => {
                if (!this.formData.path_params_rules[paramName]) {
                    // Only auto-create if not already exists
                    this.$set(this.formData.path_params_rules, paramName, {
                        type: 'string',
                        required: true,
                        prevent_path_traversal: true,
                        forbidden_patterns: [],
                        regex: '',
                        min_length: null,
                        max_length: null,
                    });
                }
            });

            // Remove rules for parameters that no longer exist in pattern
            Object.keys(this.formData.path_params_rules).forEach(existingParam => {
                if (!detectedParams.includes(existingParam)) {
                    this.$delete(this.formData.path_params_rules, existingParam);
                }
            });
        },

        addRule(context) {
            this.currentRuleContext = context;
            this.currentRuleKey = '';
            
            const rulesKey = this.getRulesKey(context);
            
            // Ensure rules object exists
            if (!this.formData[rulesKey]) {
                this.$set(this.formData, rulesKey, {});
            }
            
            this.$nextTick(() => {
                // Use jQuery modal if available, otherwise use Bootstrap modal
                const $modal = $('#ruleKeyModal');
                if ($modal.length) {
                    $modal.modal('show');
                    setTimeout(() => {
                        $('#ruleKeyInput').focus();
                    }, 300);
                } else {
                    // Fallback: try to show modal using Bootstrap 4/5 API
                    const modalElement = document.getElementById('ruleKeyModal');
                    if (modalElement) {
                        // Bootstrap 5
                        if (typeof bootstrap !== 'undefined') {
                            const modal = new bootstrap.Modal(modalElement);
                            modal.show();
                        }
                        // Bootstrap 4
                        else if (typeof $ !== 'undefined') {
                            $(modalElement).modal('show');
                        }
                        setTimeout(() => {
                            const input = document.getElementById('ruleKeyInput');
                            if (input) input.focus();
                        }, 300);
                    }
                }
            });
        },

        getRuleContextLabel() {
            const labels = {
                'path_params': 'Path Parameter',
                'query': 'Query Parameter',
                'payload': 'Payload Field',
                'headers': 'Header'
            };
            return labels[this.currentRuleContext] || 'Rule';
        },

        getRuleContextPlaceholder() {
            const placeholders = {
                'path_params': 'id',
                'query': 'company_id',
                'payload': 'name',
                'headers': 'Authorization'
            };
            return placeholders[this.currentRuleContext] || 'field_name';
        },

        getRuleContextHint() {
            const hints = {
                'path_params': 'Enter the parameter name from your path pattern (e.g., "id" for /users/{id})',
                'query': 'Enter the query parameter name (e.g., "page", "limit")',
                'payload': 'Enter the payload field name (e.g., "email", "user.name")',
                'headers': 'Enter the header name (e.g., "Authorization", "X-API-Key")'
            };
            return hints[this.currentRuleContext] || 'Enter field name';
        },

        saveRule() {
            if (!this.currentRuleKey || !this.currentRuleKey.trim()) {
                alert('Please enter a field name');
                return;
            }

            const ruleKey = this.currentRuleKey.trim();
            
            const rulesKey = this.getRulesKey(this.currentRuleContext);

            // Check if rule already exists (case-insensitive for headers)
            const existingRules = this.formData[rulesKey] || {};
            const ruleExists = Object.keys(existingRules).some(key => 
                key.toLowerCase() === ruleKey.toLowerCase()
            );
            
            if (ruleExists) {
                alert(`A rule for "${ruleKey}" already exists`);
                return;
            }

            const rule = {
                type: ['string'], // Default to array with single type, user can add more via checkboxes
                required: this.currentRuleContext === 'path_params',
                prevent_path_traversal: this.currentRuleContext !== 'headers', // Headers don't need path traversal check
                forbidden_patterns: [],
                regex: '',
                min_length: null,
                max_length: null,
            };

            // Initialize rules object if needed - MUST use $set for reactivity
            if (!this.formData[rulesKey]) {
                this.$set(this.formData, rulesKey, {});
            }

            // CRITICAL FIX: Create a completely new object to force Vue reactivity
            // Vue doesn't always detect nested object property changes, so we replace the entire object
            const currentRules = this.formData[rulesKey] || {};
            const newRules = {
                ...currentRules,
                [ruleKey]: rule
            };
            
            // Replace the entire rules object - this forces Vue to detect the change
            this.$set(this.formData, rulesKey, newRules);
            
            // Double-check: Force update the specific rule as well
            this.$nextTick(() => {
                if (this.formData[rulesKey] && this.formData[rulesKey][ruleKey]) {
                    // Rule exists, but force Vue to re-render
                    this.$forceUpdate();
                }
            });
            
            // Debug: Log to verify rule was added
            console.log('Rule added:', {
                context: this.currentRuleContext,
                rulesKey: rulesKey,
                ruleKey: ruleKey,
                rule: rule,
                formDataRules: this.formData[rulesKey],
                headerRulesExists: !!this.formData.header_rules,
                headerRulesKeys: this.formData.header_rules ? Object.keys(this.formData.header_rules) : [],
                allFormDataKeys: Object.keys(this.formData),
                headerRulesCount: this.formData.header_rules ? Object.keys(this.formData.header_rules).length : 0,
                newRulesKeys: Object.keys(newRules)
            });

            // Hide modal
            const $modal = $('#ruleKeyModal');
            if ($modal.length) {
                $modal.modal('hide');
            } else {
                const modalElement = document.getElementById('ruleKeyModal');
                if (modalElement) {
                    if (typeof bootstrap !== 'undefined') {
                        const modal = bootstrap.Modal.getInstance(modalElement);
                        if (modal) modal.hide();
                    } else if (typeof $ !== 'undefined') {
                        $(modalElement).modal('hide');
                    }
                }
            }
            
            this.currentRuleKey = '';

            // Switch to the appropriate tab to show the new rule
            this.activeTab = this.currentRuleContext;
            
            // Force Vue to update to show the new rule - multiple strategies
            this.$nextTick(() => {
                // Verify the rule exists
                if (this.formData[rulesKey] && this.formData[rulesKey][ruleKey]) {
                    console.log('Rule confirmed in formData after $nextTick');
                    this.$forceUpdate();
                    
                    // Additional force update after a short delay
                    setTimeout(() => {
                        this.$forceUpdate();
                    }, 50);
                } else {
                    console.error('Rule NOT found in formData after $nextTick!', {
                        rulesKey: rulesKey,
                        ruleKey: ruleKey,
                        formDataRules: this.formData[rulesKey]
                    });
                }
            });
        },

        cancelRule() {
            const $modal = $('#ruleKeyModal');
            if ($modal.length) {
                $modal.modal('hide');
            } else {
                const modalElement = document.getElementById('ruleKeyModal');
                if (modalElement) {
                    if (typeof bootstrap !== 'undefined') {
                        const modal = bootstrap.Modal.getInstance(modalElement);
                        if (modal) modal.hide();
                    } else if (typeof $ !== 'undefined') {
                        $(modalElement).modal('hide');
                    }
                }
            }
            this.currentRuleKey = '';
        },

        removeRule(context, key) {
            const rulesKey = this.getRulesKey(context);
            this.$delete(this.formData[rulesKey], key);
        },

        updateRule(context, key, field, value) {
            const rulesKey = this.getRulesKey(context);
            if (!this.formData[rulesKey][key]) {
                this.$set(this.formData[rulesKey], key, {});
            }
            this.$set(this.formData[rulesKey][key], field, value);
        },

        addForbiddenPattern(context, key) {
            const inputKey = `${context}-${key}`;
            const pattern = this.forbiddenPatternInputs[inputKey] || '';
            
            if (!pattern || !pattern.trim()) {
                alert('Please enter a forbidden pattern');
                return;
            }

            const rulesKey = this.getRulesKey(context);
            if (!this.formData[rulesKey][key].forbidden_patterns) {
                this.$set(this.formData[rulesKey][key], 'forbidden_patterns', []);
            }

            this.formData[rulesKey][key].forbidden_patterns.push(pattern.trim());
            this.$set(this.forbiddenPatternInputs, inputKey, '');
        },

        removeForbiddenPattern(context, key, index) {
            const rulesKey = this.getRulesKey(context);
            if (this.formData[rulesKey][key] && this.formData[rulesKey][key].forbidden_patterns) {
                this.formData[rulesKey][key].forbidden_patterns.splice(index, 1);
            }
        },

        /**
         * Check if a type is selected for a rule.
         */
        isTypeSelected(context, key, type) {
            const rulesKey = this.getRulesKey(context);
            const rule = this.formData[rulesKey][key];
            if (!rule || !rule.type) {
                return false;
            }
            
            // Handle both array (multiple types) and string (single type)
            if (Array.isArray(rule.type)) {
                return rule.type.includes(type);
            }
            return rule.type === type;
        },

        /**
         * Get array of selected types for a rule.
         */
        getSelectedTypes(context, key) {
            const rulesKey = this.getRulesKey(context);
            const rule = this.formData[rulesKey][key];
            if (!rule || !rule.type) {
                return [];
            }
            
            // Convert single type to array, or return array as-is
            return Array.isArray(rule.type) ? rule.type : [rule.type];
        },

        /**
         * Toggle a type selection for a rule.
         */
        toggleType(context, key, type, isChecked) {
            const rulesKey = this.getRulesKey(context);
            if (!this.formData[rulesKey][key]) {
                this.$set(this.formData[rulesKey], key, {});
            }
            
            const rule = this.formData[rulesKey][key];
            let currentTypes = [];
            
            // Get current types (handle both array and string)
            if (rule.type) {
                currentTypes = Array.isArray(rule.type) ? [...rule.type] : [rule.type];
            }
            
            if (isChecked) {
                // Add type if not already present
                if (!currentTypes.includes(type)) {
                    currentTypes.push(type);
                }
            } else {
                // Remove type
                currentTypes = currentTypes.filter(t => t !== type);
            }
            
            // Update rule type (use array if multiple, string if single)
            if (currentTypes.length === 0) {
                // Don't allow empty - keep at least one
                this.$set(rule, 'type', 'string');
            } else if (currentTypes.length === 1) {
                this.$set(rule, 'type', currentTypes[0]);
            } else {
                this.$set(rule, 'type', currentTypes);
            }
        },
    },
};
</script>

<template>
    <div>
        <!-- Tabs -->
        <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
                <a class="nav-link" :class="{ active: activeTab === 'basic' }" href="#" @click.prevent="activeTab = 'basic'">Basic</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" :class="{ active: activeTab === 'path_params' }" href="#" @click.prevent="activeTab = 'path_params'">Path Parameters</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" :class="{ active: activeTab === 'query' }" href="#" @click.prevent="activeTab = 'query'">Query Parameters</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" :class="{ active: activeTab === 'payload' }" href="#" @click.prevent="activeTab = 'payload'">Payload</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" :class="{ active: activeTab === 'headers' }" href="#" @click.prevent="activeTab = 'headers'">Headers</a>
            </li>
        </ul>

        <!-- Basic Tab -->
        <div v-if="activeTab === 'basic'" class="tab-content">
            <div class="form-group">
                <label>Name (optional)</label>
                <input type="text" class="form-control" v-model="formData.name" placeholder="e.g., API Users Endpoint">
            </div>

            <div class="form-group">
                <label>Path Pattern <span class="text-danger">*</span></label>
                <input type="text" class="form-control" v-model="formData.path_pattern" placeholder="/api/users/{id} or /cars/{car_id}/company/{company_id}">
                <small class="form-text text-muted">
                    <strong>Use <code>{parameter_name}</code> for dynamic segments</strong> (e.g., <code>{id}</code>, <code>{user_id}</code>)
                    <br>Rules will be automatically created in the <strong>Path Parameters</strong> tab!
                    <br>Use <code>*</code> for wildcards (no parameter rules), or enable regex below
                </small>
                <div v-if="formData.path_pattern" class="mt-2">
                    <small class="text-success">
                        <strong>Detected parameters:</strong>
                        <span v-if="Object.keys(formData.path_params_rules).length > 0">
                            <code v-for="(rule, paramName) in formData.path_params_rules" :key="paramName" class="mr-2">{{ paramName }}</code>
                        </span>
                        <span v-else class="text-muted">None - add {parameter} to your pattern</span>
                    </small>
                </div>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" v-model="formData.is_regex"> Use Regular Expression
                </label>
                <small class="form-text text-muted d-block">
                    When enabled, path pattern will be treated as a regex pattern
                </small>
            </div>

            <div class="form-group">
                <label>HTTP Method (optional)</label>
                <select class="form-control" v-model="formData.method">
                    <option value="">Any Method</option>
                    <option value="GET">GET</option>
                    <option value="POST">POST</option>
                    <option value="PUT">PUT</option>
                    <option value="PATCH">PATCH</option>
                    <option value="DELETE">DELETE</option>
                    <option value="OPTIONS">OPTIONS</option>
                    <option value="HEAD">HEAD</option>
                </select>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" v-model="formData.enabled"> Enabled
                </label>
            </div>
        </div>

        <!-- Path Parameters Tab -->
        <div v-if="activeTab === 'path_params'" class="tab-content">
            <div class="alert alert-info mb-3">
                <strong>Path Parameters:</strong> Rules are automatically created from your pattern! 
                <br>If your pattern is <code>/api/users/{id}</code>, a rule for <code>id</code> is created automatically.
                <br>Just configure the validation rules below.
            </div>

            <div v-if="Object.keys(formData.path_params_rules).length === 0" class="alert alert-warning">
                <strong>No path parameters detected!</strong>
                <br>Add parameters to your pattern in the <strong>Basic</strong> tab using curly braces:
                <br><code>/api/users/{id}</code> or <code>/cars/{car_id}/company/{company_id}</code>
            </div>

            <div v-for="(rule, key) in formData.path_params_rules" :key="`path_params_${key}`" class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center bg-light">
                    <strong class="text-primary">{{ key }}</strong>
                    <button class="btn btn-sm btn-danger" @click="removeRule('path_params', key)">Remove</button>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Allowed Types (Select Multiple)</label>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" :checked="isTypeSelected('path_params', key, 'string')" @change="toggleType('path_params', key, 'string', $event.target.checked)">
                            <label class="form-check-label">String</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" :checked="isTypeSelected('path_params', key, 'integer')" @change="toggleType('path_params', key, 'integer', $event.target.checked)">
                            <label class="form-check-label">Integer</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" :checked="isTypeSelected('path_params', key, 'numeric')" @change="toggleType('path_params', key, 'numeric', $event.target.checked)">
                            <label class="form-check-label">Numeric</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" :checked="isTypeSelected('path_params', key, 'uuid')" @change="toggleType('path_params', key, 'uuid', $event.target.checked)">
                            <label class="form-check-label">UUID</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" :checked="isTypeSelected('path_params', key, 'alphanumeric')" @change="toggleType('path_params', key, 'alphanumeric', $event.target.checked)">
                            <label class="form-check-label">Alphanumeric</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" :checked="isTypeSelected('path_params', key, 'email')" @change="toggleType('path_params', key, 'email', $event.target.checked)">
                            <label class="form-check-label">Email</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" :checked="isTypeSelected('path_params', key, 'url')" @change="toggleType('path_params', key, 'url', $event.target.checked)">
                            <label class="form-check-label">URL</label>
                        </div>
                        <small class="form-text text-muted">Select one or more types. Value must match at least one selected type.</small>
                        <div v-if="getSelectedTypes('path_params', key).length === 0" class="text-danger mt-1">
                            <small>⚠️ At least one type must be selected</small>
                        </div>
                    </div>

                    <div v-if="rule.type === 'custom'" class="form-group">
                        <label>Custom Regex Pattern</label>
                        <input type="text" class="form-control" :value="rule.regex || ''" @input="updateRule('path_params', key, 'regex', $event.target.value)" placeholder="^[0-9]+$">
                        <small class="form-text text-muted">Pattern will be validated for security</small>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" :checked="rule.required !== false" @change="updateRule('path_params', key, 'required', $event.target.checked)"> Required
                        </label>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" :checked="rule.prevent_path_traversal !== false" @change="updateRule('path_params', key, 'prevent_path_traversal', $event.target.checked)"> Prevent Path Traversal
                        </label>
                    </div>

                    <div class="form-group">
                        <label>Forbidden Patterns</label>
                        <div class="input-group mb-2">
                            <input type="text" class="form-control" v-model="forbiddenPatternInputs[`path_params-${key}`]" placeholder="../" @keyup.enter="addForbiddenPattern('path_params', key)">
                            <div class="input-group-append">
                                <button class="btn btn-secondary" @click="addForbiddenPattern('path_params', key)">Add</button>
                            </div>
                        </div>
                        <div v-if="rule.forbidden_patterns && rule.forbidden_patterns.length > 0">
                            <span v-for="(pattern, index) in rule.forbidden_patterns" :key="index" class="badge badge-warning mr-1 mb-1">
                                {{ pattern }}
                                <a href="#" @click.prevent="removeForbiddenPattern('path_params', key, index)" class="text-white ml-1">×</a>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Query Parameters Tab -->
        <div v-if="activeTab === 'query'" class="tab-content">
            <button class="btn btn-sm btn-primary mb-3" @click="addRule('query')">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 16px; height: 16px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Query Parameter Rule
            </button>

            <div v-if="Object.keys(formData.query_rules).length === 0" class="alert alert-secondary">
                No query parameter rules defined yet. Click "Add Query Parameter Rule" to create one.
            </div>

            <div v-for="(rule, key) in formData.query_rules" :key="`query_${key}`" class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center bg-light">
                    <strong class="text-primary">{{ key }}</strong>
                    <button class="btn btn-sm btn-danger" @click="removeRule('query', key)">Remove</button>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Allowed Types (Select Multiple)</label>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" :checked="isTypeSelected('query', key, 'string')" @change="toggleType('query', key, 'string', $event.target.checked)">
                            <label class="form-check-label">String</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" :checked="isTypeSelected('query', key, 'integer')" @change="toggleType('query', key, 'integer', $event.target.checked)">
                            <label class="form-check-label">Integer</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" :checked="isTypeSelected('query', key, 'numeric')" @change="toggleType('query', key, 'numeric', $event.target.checked)">
                            <label class="form-check-label">Numeric</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" :checked="isTypeSelected('query', key, 'uuid')" @change="toggleType('query', key, 'uuid', $event.target.checked)">
                            <label class="form-check-label">UUID</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" :checked="isTypeSelected('query', key, 'alphanumeric')" @change="toggleType('query', key, 'alphanumeric', $event.target.checked)">
                            <label class="form-check-label">Alphanumeric</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" :checked="isTypeSelected('query', key, 'email')" @change="toggleType('query', key, 'email', $event.target.checked)">
                            <label class="form-check-label">Email</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" :checked="isTypeSelected('query', key, 'url')" @change="toggleType('query', key, 'url', $event.target.checked)">
                            <label class="form-check-label">URL</label>
                        </div>
                        <small class="form-text text-muted">Select one or more types. Value must match at least one selected type.</small>
                        <div v-if="getSelectedTypes('query', key).length === 0" class="text-danger mt-1">
                            <small>⚠️ At least one type must be selected</small>
                        </div>
                    </div>

                    <div v-if="rule.type === 'custom'" class="form-group">
                        <label>Custom Regex Pattern</label>
                        <input type="text" class="form-control" :value="rule.regex || ''" @input="updateRule('query', key, 'regex', $event.target.value)" placeholder="^[a-zA-Z0-9]+$">
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" :checked="rule.required || false" @change="updateRule('query', key, 'required', $event.target.checked)"> Required
                        </label>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" :checked="rule.prevent_path_traversal !== false" @change="updateRule('query', key, 'prevent_path_traversal', $event.target.checked)"> Prevent Path Traversal
                        </label>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Min Length</label>
                                <input type="number" class="form-control" :value="rule.min_length || ''" @input="updateRule('query', key, 'min_length', $event.target.value ? parseInt($event.target.value) : null)">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Max Length</label>
                                <input type="number" class="form-control" :value="rule.max_length || ''" @input="updateRule('query', key, 'max_length', $event.target.value ? parseInt($event.target.value) : null)">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Forbidden Patterns</label>
                        <div class="input-group mb-2">
                            <input type="text" class="form-control" v-model="forbiddenPatternInputs[`query-${key}`]" placeholder="../" @keyup.enter="addForbiddenPattern('query', key)">
                            <div class="input-group-append">
                                <button class="btn btn-secondary" @click="addForbiddenPattern('query', key)">Add</button>
                            </div>
                        </div>
                        <div v-if="rule.forbidden_patterns && rule.forbidden_patterns.length > 0">
                            <span v-for="(pattern, index) in rule.forbidden_patterns" :key="index" class="badge badge-warning mr-1 mb-1">
                                {{ pattern }}
                                <a href="#" @click.prevent="removeForbiddenPattern('query', key, index)" class="text-white ml-1">×</a>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payload Tab -->
        <div v-if="activeTab === 'payload'" class="tab-content">
            <button class="btn btn-sm btn-primary mb-3" @click="addRule('payload')">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 16px; height: 16px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Payload Field Rule
            </button>

            <div v-if="Object.keys(formData.payload_rules).length === 0" class="alert alert-secondary">
                No payload rules defined yet. Click "Add Payload Field Rule" to create one.
            </div>

            <div v-for="(rule, key) in formData.payload_rules" :key="`payload_${key}`" class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center bg-light">
                    <strong class="text-primary">{{ key }}</strong>
                    <button class="btn btn-sm btn-danger" @click="removeRule('payload', key)">Remove</button>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Allowed Types (Select Multiple)</label>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" :checked="isTypeSelected('payload', key, 'string')" @change="toggleType('payload', key, 'string', $event.target.checked)">
                            <label class="form-check-label">String</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" :checked="isTypeSelected('payload', key, 'integer')" @change="toggleType('payload', key, 'integer', $event.target.checked)">
                            <label class="form-check-label">Integer</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" :checked="isTypeSelected('payload', key, 'numeric')" @change="toggleType('payload', key, 'numeric', $event.target.checked)">
                            <label class="form-check-label">Numeric</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" :checked="isTypeSelected('payload', key, 'uuid')" @change="toggleType('payload', key, 'uuid', $event.target.checked)">
                            <label class="form-check-label">UUID</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" :checked="isTypeSelected('payload', key, 'alphanumeric')" @change="toggleType('payload', key, 'alphanumeric', $event.target.checked)">
                            <label class="form-check-label">Alphanumeric</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" :checked="isTypeSelected('payload', key, 'email')" @change="toggleType('payload', key, 'email', $event.target.checked)">
                            <label class="form-check-label">Email</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" :checked="isTypeSelected('payload', key, 'url')" @change="toggleType('payload', key, 'url', $event.target.checked)">
                            <label class="form-check-label">URL</label>
                        </div>
                        <small class="form-text text-muted">Select one or more types. Value must match at least one selected type.</small>
                        <div v-if="getSelectedTypes('payload', key).length === 0" class="text-danger mt-1">
                            <small>⚠️ At least one type must be selected</small>
                        </div>
                    </div>

                    <div v-if="rule.type === 'custom'" class="form-group">
                        <label>Custom Regex Pattern</label>
                        <input type="text" class="form-control" :value="rule.regex || ''" @input="updateRule('payload', key, 'regex', $event.target.value)" placeholder="^[a-zA-Z0-9]+$">
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" :checked="rule.required || false" @change="updateRule('payload', key, 'required', $event.target.checked)"> Required
                        </label>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" :checked="rule.prevent_path_traversal !== false" @change="updateRule('payload', key, 'prevent_path_traversal', $event.target.checked)"> Prevent Path Traversal
                        </label>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Min Length</label>
                                <input type="number" class="form-control" :value="rule.min_length || ''" @input="updateRule('payload', key, 'min_length', $event.target.value ? parseInt($event.target.value) : null)">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Max Length</label>
                                <input type="number" class="form-control" :value="rule.max_length || ''" @input="updateRule('payload', key, 'max_length', $event.target.value ? parseInt($event.target.value) : null)">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Forbidden Patterns</label>
                        <div class="input-group mb-2">
                            <input type="text" class="form-control" v-model="forbiddenPatternInputs[`payload-${key}`]" placeholder="../" @keyup.enter="addForbiddenPattern('payload', key)">
                            <div class="input-group-append">
                                <button class="btn btn-secondary" @click="addForbiddenPattern('payload', key)">Add</button>
                            </div>
                        </div>
                        <div v-if="rule.forbidden_patterns && rule.forbidden_patterns.length > 0">
                            <span v-for="(pattern, index) in rule.forbidden_patterns" :key="index" class="badge badge-warning mr-1 mb-1">
                                {{ pattern }}
                                <a href="#" @click.prevent="removeForbiddenPattern('payload', key, index)" class="text-white ml-1">×</a>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Headers Tab -->
        <div v-if="activeTab === 'headers'" class="tab-content">
            <button class="btn btn-sm btn-primary mb-3" @click="addRule('headers')">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 16px; height: 16px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Header Rule
            </button>

            <div v-if="!formData.header_rules || Object.keys(formData.header_rules).length === 0" class="alert alert-secondary">
                No header rules defined yet. Click "Add Header Rule" to create one.
            </div>

            <div v-for="(rule, key) in formData.header_rules" :key="`headers_${key}_${JSON.stringify(rule)}`" class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center bg-light">
                    <strong class="text-primary">{{ key }}</strong>
                    <button class="btn btn-sm btn-danger" @click="removeRule('headers', key)">Remove</button>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Allowed Types (Select Multiple)</label>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" :checked="isTypeSelected('headers', key, 'string')" @change="toggleType('headers', key, 'string', $event.target.checked)">
                            <label class="form-check-label">String</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" :checked="isTypeSelected('headers', key, 'integer')" @change="toggleType('headers', key, 'integer', $event.target.checked)">
                            <label class="form-check-label">Integer</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" :checked="isTypeSelected('headers', key, 'numeric')" @change="toggleType('headers', key, 'numeric', $event.target.checked)">
                            <label class="form-check-label">Numeric</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" :checked="isTypeSelected('headers', key, 'uuid')" @change="toggleType('headers', key, 'uuid', $event.target.checked)">
                            <label class="form-check-label">UUID</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" :checked="isTypeSelected('headers', key, 'alphanumeric')" @change="toggleType('headers', key, 'alphanumeric', $event.target.checked)">
                            <label class="form-check-label">Alphanumeric</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" :checked="isTypeSelected('headers', key, 'email')" @change="toggleType('headers', key, 'email', $event.target.checked)">
                            <label class="form-check-label">Email</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" :checked="isTypeSelected('headers', key, 'url')" @change="toggleType('headers', key, 'url', $event.target.checked)">
                            <label class="form-check-label">URL</label>
                        </div>
                        <small class="form-text text-muted">Select one or more types. Value must match at least one selected type.</small>
                        <div v-if="getSelectedTypes('headers', key).length === 0" class="text-danger mt-1">
                            <small>⚠️ At least one type must be selected</small>
                        </div>
                    </div>

                    <div v-if="(Array.isArray(rule.type) && rule.type.includes('custom')) || rule.type === 'custom'" class="form-group">
                        <label>Custom Regex Pattern</label>
                        <input type="text" class="form-control" :value="rule.regex || ''" @input="updateRule('headers', key, 'regex', $event.target.value)" placeholder="^Bearer .+$">
                        <small class="form-text text-muted">Pattern will be validated for security</small>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" :checked="rule.required || false" @change="updateRule('headers', key, 'required', $event.target.checked)"> Required
                        </label>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Min Length</label>
                                <input type="number" class="form-control" :value="rule.min_length || ''" @input="updateRule('headers', key, 'min_length', $event.target.value ? parseInt($event.target.value) : null)">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Max Length</label>
                                <input type="number" class="form-control" :value="rule.max_length || ''" @input="updateRule('headers', key, 'max_length', $event.target.value ? parseInt($event.target.value) : null)">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Forbidden Patterns</label>
                        <div class="input-group mb-2">
                            <input type="text" class="form-control" v-model="forbiddenPatternInputs[`headers-${key}`]" placeholder="../" @keyup.enter="addForbiddenPattern('headers', key)">
                            <div class="input-group-append">
                                <button class="btn btn-secondary" @click="addForbiddenPattern('headers', key)">Add</button>
                            </div>
                        </div>
                        <div v-if="rule.forbidden_patterns && rule.forbidden_patterns.length > 0">
                            <span v-for="(pattern, index) in rule.forbidden_patterns" :key="index" class="badge badge-warning mr-1 mb-1">
                                {{ pattern }}
                                <a href="#" @click.prevent="removeForbiddenPattern('headers', key, index)" class="text-white ml-1">×</a>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rule Key Modal -->
        <div
            class="modal fade"
            id="ruleKeyModal"
            tabindex="-1"
            role="dialog"
            aria-labelledby="ruleKeyModalLabel"
            aria-hidden="true"
        >
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add {{ getRuleContextLabel() }} Rule</h5>
                        <button type="button" class="close" @click="cancelRule">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Field Name <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control"
                                v-model="currentRuleKey"
                                :placeholder="getRuleContextPlaceholder()"
                                @keyup.enter="saveRule"
                                id="ruleKeyInput"
                            >
                            <small class="form-text text-muted">{{ getRuleContextHint() }}</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" @click="cancelRule">Cancel</button>
                        <button class="btn btn-primary" @click="saveRule">Add Rule</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
