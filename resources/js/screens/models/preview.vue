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
                <td class="table-fit text-muted">Model</td>
                <td>
                    {{slotProps.entry.content.model}}
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Action</td>
                <td>
                    <span class="badge" :class="'badge-'+modelActionClass(slotProps.entry.content.action)">
                        {{slotProps.entry.content.action}}
                    </span>
                </td>
            </tr>

            <tr v-if="slotProps.entry.content.count">
                <td class="table-fit text-muted">Hydrated</td>
                <td>
                    {{slotProps.entry.content.count}}
                </td>
            </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps">
            <div class="card mt-5 overflow-hidden" v-if="slotProps.entry.content.action != 'deleted' && slotProps.entry.content.changes">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link active">Changes</a>
                    </li>
                </ul>

                <div class="code-bg p-4 mb-0 text-white">
                    <copy-clipboard :data="slotProps.entry.content.changes">
                        <vue-json-pretty :data="slotProps.entry.content.changes"></vue-json-pretty>
                    </copy-clipboard>
                </div>
            </div>
        </div>
    </preview-screen>
</template>
