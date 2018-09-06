<script type="text/ecmascript-6">
    import axios from 'axios';

    export default {
        data(){
            return {
                entry: null,
                batch: [],
            };
        },

        methods: {
            batchEntriesOfType(type){
                return _.filter(this.batch, {type: type})
            }
        }
    }
</script>

<template>
    <preview-screen title="Command Details" resource="requests" :id="$route.params.id">
        <template slot="table-parameters" slot-scope="slotProps">
            <tr>
                <td class="table-fit font-weight-bold">Command</td>
                <td>
                    {{slotProps.entry.content.command}}
                </td>
            </tr>

            <tr>
                <td class="table-fit font-weight-bold">Exit Code</td>
                <td>
                    {{slotProps.entry.content.exit_code}}
                </td>
            </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps">
            <div class="card mt-5" v-if="slotProps.entry.content.arguments">
                <div class="card-header"><h5>Arguments</h5></div>

                <pre class="bg-dark p-4 mb-0 text-white">{{slotProps.entry.content.arguments}}</pre>
            </div>

            <div class="card mt-5" v-if="slotProps.entry.content.options">
                <div class="card-header"><h5>Options</h5></div>

                <pre class="bg-dark p-4 mb-0 text-white">{{slotProps.entry.content.options}}</pre>
            </div>

            <!-- Additional Information -->
            <related-entries :entry="entry" :batch="batch">
            </related-entries>
        </div>
    </preview-screen>
</template>
