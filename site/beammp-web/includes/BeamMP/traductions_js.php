<script>
    const t = <?= json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); ?>;
    function tr(key, vars = {}) {
        let str = t[key] || key;
        for (const k in vars) {
            str = str.replace(`{${k}}`, vars[k]);
        }
        return str;
    }
</script>