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
    <preview-screen title="Model Action" resource="hydrations" :id="$route.params.id">
        <template slot="table-parameters" slot-scope="slotProps">
            <tr>
                <td class="table-fit font-weight-bold">Model</td>
                <td>
                    {{slotProps.entry.content.model}}
                </td>
            </tr>

            <tr>
                <td class="table-fit font-weight-bold">Hydrations</td>
                <td>
                    <span class="badge font-weight-light">
                        {{slotProps.entry.content.count}}
                    </span>
                </td>
            </tr>
        </template>
    </preview-screen>
</template>
