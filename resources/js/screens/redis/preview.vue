<script type="text/ecmascript-6">
export default {
    data(){
        return {
            entry: null,
            batch: [],
        };
    },
}
</script>

<template>
    <preview-screen title="Redis Command Details" resource="redis" :id="$route.params.id">
        <template slot="table-parameters" slot-scope="slotProps">
            <tr>
                <td class="table-fit text-muted">Connection</td>
                <td>
                    {{ slotProps.entry.content.connection }}
                </td>
            </tr>

            <tr>
                <td class="table-fit text-muted">Duration</td>
                <td>{{ slotProps.entry.content.time }}ms</td>
            </tr>
        </template>

        <div slot="after-attributes-card" slot-scope="slotProps">
            <div class="card mt-5 overflow-hidden">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link active">Command</a>
                    </li>
                </ul>

                <pre class="code-bg p-4 mb-0 text-white">{{ slotProps.entry.content.command }}</pre>
            </div>
        </div>
    </preview-screen>
</template>
