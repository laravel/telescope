<script type="text/ecmascript-6">
    export default {
        data(){
            return {
                entry: null,
                batch: [],
            };
        }
    }
</script>

<template>
    <preview-screen title="Scheduled Command Details" resource="requests" :id="$route.params.id">
        <template slot="table-parameters" slot-scope="slotProps">
            <tr>
                <td class="table-fit text-muted">Description</td>
                <td>
                    {{slotProps.entry.content.description || '-'}}
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Command</td>
                <td>
                    <code>{{slotProps.entry.content.command || '-'}}</code>
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Expression</td>
                <td>
                    {{slotProps.entry.content.expression}}
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">User</td>
                <td>
                    {{slotProps.entry.content.user || '-'}}
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Timezone</td>
                <td>
                    {{slotProps.entry.content.timezone || '-'}}
                </td>
            </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps" v-if="slotProps.entry.content.output">
            <div class="card mt-5 overflow-hidden">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link active">Output</a>
                    </li>
                </ul>

                <pre class="code-bg p-4 mb-0 text-white">{{ slotProps.entry.content.output }}</pre>
            </div>
        </div>
    </preview-screen>
</template>
