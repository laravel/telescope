<script type="text/ecmascript-6">
    import _ from 'lodash';
    import StylesMixin from './../../mixins/entriesStyles';

    export default {
        mixins: [
            StylesMixin,
        ],


        data(){
            return {
                entry: null,
                batch: [],
            };
        },
    }
</script>

<template>
    <preview-screen title="View Action" resource="views" :id="$route.params.id">
        <template slot="table-parameters" slot-scope="slotProps">
            <tr>
                <td class="table-fit font-weight-bold">View</td>
                <td>
                    {{slotProps.entry.content.name}}
                </td>
            </tr>

            <tr>
                <td class="table-fit font-weight-bold">Path</td>
                <td>
                    {{slotProps.entry.content.path}}
                </td>
            </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps">
            <div class="card mt-5" v-if="slotProps.entry.content.data">
                <div class="card-header"><h5>Data</h5></div>

                <div class="code-bg p-4 mb-0 text-white">
                    <vue-json-pretty :data="slotProps.entry.content.data"></vue-json-pretty>
                </div>
            </div>
        </div>
    </preview-screen>
</template>
