<script type="text/ecmascript-6">
    import StylesMixin from './../../mixins/entriesStyles';

    export default {
        mixins: [
            StylesMixin,
        ],


        data() {
            return {
                entry: null,
                batch: [],
            };
        }
    }
</script>

<template>
    <preview-screen title="Gate Details" resource="gates" :id="$route.params.id">
        <template slot="table-parameters" slot-scope="slotProps">
            <tr>
                <td class="table-fit text-muted">Ability</td>
                <td>
                    {{slotProps.entry.content.ability}}
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Result</td>
                <td>
                    <span class="badge" :class="'badge-'+gateResultClass(slotProps.entry.content.result)">
                        {{slotProps.entry.content.result}}
                    </span>
                </td>
            </tr>

            <tr  v-if="slotProps.entry.content.file">
                <td class="table-fit text-muted">Location</td>
                <td>
                    {{slotProps.entry.content.file}}:{{slotProps.entry.content.line}}
                </td>
            </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps">
            <div class="card mt-5 overflow-hidden">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link active">Arguments</a>
                    </li>
                </ul>

                <div class="code-bg p-4 mb-0 text-white">
                    <copy-clipboard :data="slotProps.entry.content.arguments">
                        <vue-json-pretty :data="slotProps.entry.content.arguments"></vue-json-pretty>
                    </copy-clipboard>
                </div>
            </div>
        </div>
    </preview-screen>
</template>
