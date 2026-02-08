<script type="text/ecmascript-6">
export default {
    props: {
        rule: {
            type: Object,
            default: null,
        },
    },

    data() {
        return {
            formData: {
                name: '',
                category: '',
                description: '',
                patterns: [],
                exclude_paths: [],
                enabled: true,
            },
            newPattern: '',
            newExcludePath: '',
            categoryOptions: [
                { value: 'path_traversal', label: 'Path Traversal' },
                { value: 'sql_injection', label: 'SQL Injection' },
                { value: 'xss', label: 'XSS (Cross-Site Scripting)' },
                { value: 'command_injection', label: 'Command Injection' },
                { value: 'ldap_injection', label: 'LDAP Injection' },
                { value: 'xml_injection', label: 'XML/XXE Injection' },
                { value: 'custom', label: 'Custom' },
            ],
        };
    },

    mounted() {
        this.loadRule();
    },

    watch: {
        rule: {
            handler(newVal) {
                if (newVal) {
                    this.loadRule();
                }
            },
            immediate: true,
            deep: true,
        },
    },

    methods: {
        loadRule() {
            if (this.rule && Object.keys(this.rule).length > 0) {
                this.formData = {
                    name: this.rule.name || '',
                    category: this.rule.category || '',
                    description: this.rule.description || '',
                    patterns: this.rule.patterns ? JSON.parse(JSON.stringify(this.rule.patterns)) : [],
                    exclude_paths: this.rule.exclude_paths ? JSON.parse(JSON.stringify(this.rule.exclude_paths)) : [],
                    enabled: this.rule.enabled !== undefined ? !!this.rule.enabled : true,
                };
            } else {
                this.formData = {
                    name: '',
                    category: 'custom',
                    description: '',
                    patterns: [],
                    exclude_paths: [],
                    enabled: true,
                };
            }
        },

        getFormData() {
            return this.formData;
        },

        addPattern() {
            if (this.newPattern && this.newPattern.trim()) {
                this.formData.patterns.push(this.newPattern.trim());
                this.newPattern = '';
            }
        },

        removePattern(index) {
            this.formData.patterns.splice(index, 1);
        },

        addExcludePath() {
            if (this.newExcludePath && this.newExcludePath.trim()) {
                this.formData.exclude_paths.push(this.newExcludePath.trim());
                this.newExcludePath = '';
            }
        },

        removeExcludePath(index) {
            this.formData.exclude_paths.splice(index, 1);
        },
    },
};
</script>

<template>
    <div>
        <div class="form-group">
            <label>Rule Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" v-model="formData.name" placeholder="Path Traversal Detection">
        </div>

        <div class="form-group">
            <label>Category <span class="text-danger">*</span></label>
            <select class="form-control" v-model="formData.category">
                <option v-for="option in categoryOptions" :key="option.value" :value="option.value">
                    {{ option.label }}
                </option>
            </select>
        </div>

        <div class="form-group">
            <label>Description (optional)</label>
            <input type="text" class="form-control" v-model="formData.description" placeholder="Brief description of this security rule">
        </div>

        <div class="form-group">
            <label>Patterns to Detect <span class="text-danger">*</span></label>
            <div class="input-group mb-2">
                <input
                    type="text"
                    class="form-control"
                    v-model="newPattern"
                    placeholder="Enter pattern (e.g., ../ or use /regex/ for regex)"
                    @keyup.enter="addPattern"
                >
                <div class="input-group-append">
                    <button class="btn btn-sm btn-secondary" @click="addPattern">Add</button>
                </div>
            </div>
            <small class="form-text text-muted">
                Enter patterns to detect. Use <code>/pattern/</code> for regex (e.g., <code>/\.\.\//</code>)
            </small>
            <div v-if="formData.patterns.length > 0" class="mt-2">
                <span v-for="(pattern, index) in formData.patterns" :key="index" class="badge badge-secondary mr-1 mb-1">
                    {{ pattern }}
                    <a href="#" @click.prevent="removePattern(index)" class="text-white ml-1">×</a>
                </span>
            </div>
            <div v-else class="text-muted mt-2">No patterns added yet</div>
        </div>

        <div class="form-group">
            <label>Exclude Paths (optional)</label>
            <div class="input-group mb-2">
                <input
                    type="text"
                    class="form-control"
                    v-model="newExcludePath"
                    placeholder="Enter path to exclude (e.g., /api/admin or /api/*)"
                    @keyup.enter="addExcludePath"
                >
                <div class="input-group-append">
                    <button class="btn btn-sm btn-secondary" @click="addExcludePath">Add</button>
                </div>
            </div>
            <small class="form-text text-muted">
                Paths where this rule will NOT be applied. Use <code>*</code> for wildcards.
            </small>
            <div v-if="formData.exclude_paths.length > 0" class="mt-2">
                <span v-for="(path, index) in formData.exclude_paths" :key="index" class="badge badge-warning mr-1 mb-1">
                    {{ path }}
                    <a href="#" @click.prevent="removeExcludePath(index)" class="text-white ml-1">×</a>
                </span>
            </div>
            <div v-else class="text-muted mt-2">No excluded paths (rule applies to all endpoints)</div>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" v-model="formData.enabled"> Enabled
            </label>
        </div>
    </div>
</template>

