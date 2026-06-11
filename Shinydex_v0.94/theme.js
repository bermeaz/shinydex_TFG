// TEMA OSCURO/CLARO
document.addEventListener('DOMContentLoaded', function() 
	{
		// Obtener el tema guardado o usar por defecto 'light'
		const savedTheme = localStorage.getItem('theme') || 'light';
		const toggleCheckbox = document.getElementById('themeToggle');
		
		// Aplicar el tema guardado
		setTheme(savedTheme);
		
		// Sincronizar el checkbox con el tema actual
		if (toggleCheckbox) 
			{
				toggleCheckbox.checked = savedTheme === 'dark';
				
				// Listener para cambiar el tema
				toggleCheckbox.addEventListener('change', function() 
					{
						const newTheme = this.checked ? 'dark' : 'light';
						setTheme(newTheme);
						localStorage.setItem('theme', newTheme);
					});
			}
	});

function setTheme(theme) 
	{
		if (theme === 'dark') 
			{
				document.documentElement.setAttribute('data-theme', 'dark');
			} 
		else 
			{
				document.documentElement.removeAttribute('data-theme');
			}
	}

