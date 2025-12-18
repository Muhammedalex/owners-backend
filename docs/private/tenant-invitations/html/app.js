// Router for single-page navigation
class DocumentationRouter {
    constructor() {
        this.routes = {
            '': '00-README.html',
            'index': '00-README.html',
            'overview': '01-overview.html',
            'database': '02-database-schema.html',
            'api-owner': '03-api-endpoints-owner.html',
            'api-public': '04-api-endpoints-public.html',
            'workflow-owner': '05-workflow-owner.html',
            'workflow-tenant': '06-workflow-tenant.html',
            'invitation-types': '07-invitation-types.html',
            'mail-config': '08-mail-configuration.html',
            'permissions': '09-permissions-security.html',
            'registration': '10-user-registration-flow.html',
            'testing': '11-testing-guide.html',
            'troubleshooting': '12-troubleshooting.html'
        };
        
        this.init();
    }
    
    init() {
        // Handle initial load
        this.handleRoute();
        
        // Handle browser back/forward
        window.addEventListener('popstate', () => this.handleRoute());
        
        // Handle link clicks
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-route]')) {
                e.preventDefault();
                const route = e.target.getAttribute('data-route');
                this.navigate(route);
            }
        });
    }
    
    navigate(route) {
        const path = this.routes[route] || this.routes[''];
        history.pushState({}, '', `#${route}`);
        this.loadContent(path);
        this.updateActiveLink(route);
    }
    
    handleRoute() {
        const hash = window.location.hash.slice(1) || '';
        const route = hash.split('/')[0] || '';
        const path = this.routes[route] || this.routes[''];
        this.loadContent(path);
        this.updateActiveLink(route);
    }
    
    async loadContent(path) {
        try {
            const response = await fetch(path);
            if (!response.ok) {
                throw new Error('File not found');
            }
            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const content = doc.querySelector('.main-content');
            
            if (content) {
                document.querySelector('.main-content').innerHTML = content.innerHTML;
                
                // Re-attach event listeners to links inside content
                document.querySelectorAll('.main-content a[data-route]').forEach(link => {
                    link.addEventListener('click', (e) => {
                        e.preventDefault();
                        const route = link.getAttribute('data-route');
                        this.navigate(route);
                    });
                });
                
                // Scroll to top
                window.scrollTo(0, 0);
            }
        } catch (error) {
            console.error('Error loading content:', error);
            document.querySelector('.main-content').innerHTML = `
                <h1>Error</h1>
                <p>Could not load the requested page. Please check the file path.</p>
                <p><small>Error: ${error.message}</small></p>
            `;
        }
    }
    
    updateActiveLink(route) {
        // Remove active class from all links
        document.querySelectorAll('[data-route]').forEach(link => {
            link.classList.remove('active');
        });
        
        // Add active class to current link
        const activeLink = document.querySelector(`[data-route="${route}"]`);
        if (activeLink) {
            activeLink.classList.add('active');
        }
    }
}

// Print functionality
function printDocumentation() {
    window.print();
}

// Initialize router when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new DocumentationRouter();
    
    // Add print button event listener
    const printBtn = document.getElementById('printBtn');
    if (printBtn) {
        printBtn.addEventListener('click', printDocumentation);
    }
});

