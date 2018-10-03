<script type="text/ecmascript-6">
    export default {
        components: {
            'code-preview': require('./../../components/ExceptionCodePreview'),
            'stack-trace': require('./../../components/Stacktrace')
        },

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
    <preview-screen title="Exception Details" resource="exceptions" :id="$route.params.id">
        <template slot="table-parameters" slot-scope="slotProps">
            <tr>
                <td class="table-fit font-weight-bold">Type</td>
                <td>
                    {{slotProps.entry.content.class}}
                </td>
            </tr>

            <tr>
                <td class="table-fit font-weight-bold">Location</td>
                <td>
                    {{slotProps.entry.content.file}}:{{slotProps.entry.content.line}}
                </td>
            </tr>

            <tr>
                <td class="table-fit font-weight-bold">Occurrences</td>
                <td>
                    <router-link :to="{name:'exceptions', query: {family_hash: slotProps.entry.family_hash}}" class="control-action">
                        View Other Occurrences
                    </router-link>
                </td>
            </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps" class="mt-5">
            <div class="card mt-5">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link" :class="{active: currentTab=='message'}" href="#" v-on:click.prevent="currentTab='message'">Message</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" :class="{active: currentTab=='location'}" href="#" v-on:click.prevent="currentTab='location'">Location</a>
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

                    <stack-trace :trace="slotProps.entry.content.trace" v-show="currentTab=='trace'"></stack-trace>
                </div>
            </div>
        </div>
    </preview-screen>
</template>

<style scoped>

</style>
