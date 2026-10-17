document.addEventListener("DOMContentLoaded", function () {
    const appLayout = document.getElementById("appLayout");
    const sidebarToggle = document.getElementById("sidebarToggle");
    const sidebarBackdrop = document.getElementById("sidebarBackdrop");
    const themeToggle = document.getElementById("themeToggle");
    const themeIcon = document.getElementById("themeIcon");
    const themeText = document.getElementById("themeText");
    const mobileLayout = window.matchMedia("(max-width: 768px)");
    const themeStorageKey = "taytay-health-theme";

    let savedTheme = null;
    try {
        savedTheme = window.localStorage.getItem(themeStorageKey);
    } catch (error) {
        savedTheme = null;
    }

    function updateTheme(isDark) {
        document.body.classList.toggle("dark-mode", isDark);

        if (themeIcon) {
            themeIcon.textContent = isDark ? "☀" : "☾";
        }

        if (themeText) {
            themeText.textContent = isDark ? "Light Mode" : "Dark Mode";
        }

        if (themeToggle) {
            themeToggle.setAttribute("aria-pressed", String(isDark));
        }
    }

    function updateSidebarState() {
        if (!appLayout || !sidebarToggle) {
            return;
        }

        const isOpen = mobileLayout.matches
            ? appLayout.classList.contains("sidebar-open")
            : !appLayout.classList.contains("sidebar-collapsed");

        sidebarToggle.setAttribute("aria-expanded", String(isOpen));

        if (sidebarBackdrop) {
            sidebarBackdrop.hidden = !(mobileLayout.matches && isOpen);
        }
    }

    updateTheme(savedTheme === "dark");
    updateSidebarState();

    if (appLayout && sidebarToggle) {
        sidebarToggle.addEventListener("click", function () {
            if (mobileLayout.matches) {
                appLayout.classList.toggle("sidebar-open");
            } else {
                appLayout.classList.toggle("sidebar-collapsed");
            }

            updateSidebarState();
        });
    }

    if (themeToggle) {
        themeToggle.addEventListener("click", function () {
            const isDark = !document.body.classList.contains("dark-mode");
            updateTheme(isDark);

            try {
                window.localStorage.setItem(
                    themeStorageKey,
                    isDark ? "dark" : "light"
                );
            } catch (error) {
                return;
            }
        });
    }

    if (sidebarBackdrop && appLayout) {
        sidebarBackdrop.addEventListener("click", function () {
            appLayout.classList.remove("sidebar-open");
            updateSidebarState();
        });
    }

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape" && appLayout && mobileLayout.matches) {
            appLayout.classList.remove("sidebar-open");
            updateSidebarState();
        }
    });

    window.addEventListener("resize", function () {
        if (!mobileLayout.matches && appLayout) {
            appLayout.classList.remove("sidebar-open");
        }

        updateSidebarState();
    });
});