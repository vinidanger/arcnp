{{-- Aplica o tema (claro/escuro), o estado do menu (retraído/expandido)
     e as categorias colapsadas da página "Tools" (template cPanel)
     ANTES do primeiro paint — sem isso a página pisca no estado padrão
     por uma fração de segundo antes do JS do final da página rodar.
     Categorias: como os elementos .cpanel-category ainda nem existem
     nesse ponto (isso roda no <head>, antes do <body> ser parseado),
     não dá pra fazer querySelector+classList aqui — em vez disso bota
     a lista de categorias colapsadas num atributo no <html>
     (data-cpanel-collapsed="site dominio"), e o app.css tem uma regra
     pra cada categoria reagindo a esse atributo via seletor [~=]. --}}
<script>
    (function () {
        var storedTheme = localStorage.getItem('theme');
        var theme = storedTheme || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        document.documentElement.setAttribute('data-theme', theme);

        if (localStorage.getItem('sidebarCollapsed') === '1') {
            document.documentElement.classList.add('sidebar-collapsed');
        }

        var collapsedCategories = [];
        for (var i = 0; i < localStorage.length; i++) {
            var key = localStorage.key(i);
            if (key && key.indexOf('cpanelCategoryCollapsed:') === 0 && localStorage.getItem(key) === '1') {
                collapsedCategories.push(key.slice('cpanelCategoryCollapsed:'.length));
            }
        }
        if (collapsedCategories.length) {
            document.documentElement.setAttribute('data-cpanel-collapsed', collapsedCategories.join(' '));
        }
    })();
</script>
