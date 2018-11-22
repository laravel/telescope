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
    <preview-screen title="Model Action" resource="models" :id="$route.params.id">
        <template slot="table-parameters" slot-scope="slotProps">
            <tr>
                <td class="table-fit font-weight-bold">Model</td>
                <td>
                    {{slotProps.entry.content.model}}
                </td>
            </tr>

            <tr>
                <td class="table-fit font-weight-bold">Action</td>
                <td>
                    <span class="badge font-weight-light" :class="'badge-'+modelActionClass(slotProps.entry.content.action)">
                        {{slotProps.entry.content.action}}
                    </span>
                </td>
            </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps">
            <div class="card mt-5" v-if="slotProps.entry.content.action != 'deleted' && slotProps.entry.content.changes">
                <div class="card-header"><h5>Changes</h5></div>

                <div class="code-bg p-4 mb-0 text-white">
                    <vue-json-pretty :data="slotProps.entry.content.changes"></vue-json-pretty>
                </div>
            </div>
        </div>
    </preview-screen>
</template>
