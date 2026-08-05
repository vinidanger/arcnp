{{-- Aplica o tema (claro/escuro) e o estado do menu (retraído/expandido)
     ANTES do primeiro paint — sem isso a página pisca no estado padrão
     por uma fração de segundo antes do JS do final da página rodar. --}}
<script>
    (function () {
        var storedTheme = localStorage.getItem('theme');
        var theme = storedTheme || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        document.documentElement.setAttribute('data-theme', theme);

        if (localStorage.getItem('sidebarCollapsed') === '1') {
            document.documentElement.classList.add('sidebar-collapsed');
        }
    })();
</script>
