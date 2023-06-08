<script type="text/ecmascript-6">
    import StylesMixin from './../../mixins/entriesStyles';

    export default {
        components: {
            'code-preview': require('./../../components/ExceptionCodePreview').default,
            'stack-trace': require('./../../components/Stacktrace').default
        },


        mixins: [
            StylesMixin,
        ],


        data(){
            return {
                entry: null,
                batch: [],
                currentTab: 'message'
            };
        },
    }
</script>

<template>
    <preview-screen title="Log Details" resource="logs" :id="$route.params.id">
        <template slot="table-parameters" slot-scope="slotProps">
            <tr>
                <td class="table-fit text-muted">Level</td>
                <td>
                    <span class="badge" :class="'badge-'+logLevelClass(slotProps.entry.content.level)">
                        {{slotProps.entry.content.level}}
                    </span>
                </td>
            </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps" class="mt-5">
            <div class="card mt-5 overflow-hidden">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link" :class="{active: currentTab=='message'}" href="#" v-on:click.prevent="currentTab='message'">Log Message</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" :class="{active: currentTab=='context'}" href="#" v-on:click.prevent="currentTab='context'">Context</a>
                    </li>
                </ul>
                <div>
                    <!-- Log Message -->
                    <div v-show="currentTab=='message'">
                        <copy-clipboard :data="slotProps.entry.content.message">
                            <pre class="code-bg p-4 mb-0 text-white">{{ slotProps.entry.content.message }}</pre>
                        </copy-clipboard>
                    </div>

                    <!-- Context -->
                    <div class="code-bg p-4 mb-0 text-white" v-show="currentTab=='context'">
                        <copy-clipboard :data="slotProps.entry.content.context">
                            <vue-json-pretty :data="slotProps.entry.content.context"></vue-json-pretty>
                        </copy-clipboard>
                    </div>
                </div>
            </div>
        </div>
    </preview-screen>
</template>

<style scoped>

</style>
