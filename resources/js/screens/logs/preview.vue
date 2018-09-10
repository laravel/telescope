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
            };
        },
    }
</script>

<template>
    <preview-screen title="Log Details" resource="logs" :id="$route.params.id">
        <template slot="table-parameters" slot-scope="slotProps">
            <tr>
                <td class="table-fit font-weight-bold">Level</td>
                <td>
                    {{slotProps.entry.content.level}}
                </td>
            </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps" class="mt-5">
            <div class="card">
                <div class="card-header">
                    <h5>Log Message</h5>
                </div>

                <pre class="bg-dark p-4 mb-0 text-white">{{slotProps.entry.content.message}}</pre>
            </div>

            <div class="card mt-5" v-if="slotProps.entry.content.context">
                <div class="card-header">
                    <h5>Context</h5>
                </div>

                <div class="bg-dark p-4 mb-0 text-white">
                    <tree-view :data="slotProps.entry.content.context" :options="{maxDepth: 3}"></tree-view>
                </div>
            </div>
        </div>
    </preview-screen>
</template>

<style scoped>

</style>
