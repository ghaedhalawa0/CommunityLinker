document.addEventListener('click', (event) => {
	if (!(event.target instanceof Element)) {
		return;
	}

	document.querySelectorAll('.post-menu[open]').forEach((menu) => {
		if (!menu.contains(event.target)) {
			menu.removeAttribute('open');
		}
	});

	const deleteTrigger = event.target.closest('[data-delete-trigger]');
	if (deleteTrigger instanceof HTMLElement) {
		const targetId = deleteTrigger.getAttribute('data-target');
		const modal = targetId ? document.getElementById(targetId) : null;

		if (modal instanceof HTMLElement) {
			event.preventDefault();
			event.stopPropagation();
			deleteTrigger.closest('.post-menu')?.removeAttribute('open');
			modal.hidden = false;
			const submitButton = modal.querySelector('button[type="submit"]');
			submitButton?.focus();
		}

		return;
	}

	const avatarTrigger = event.target.closest('[data-avatar-trigger]');
	if (avatarTrigger instanceof HTMLButtonElement) {
		event.preventDefault();
		const menuId = avatarTrigger.getAttribute('aria-controls');
		const menu = menuId ? document.getElementById(menuId) : null;

		if (menu instanceof HTMLElement) {
			document.querySelectorAll('[data-avatar-menu]').forEach((otherMenu) => {
				if (otherMenu !== menu && otherMenu instanceof HTMLElement) {
					otherMenu.hidden = true;
				}
			});
			menu.hidden = !menu.hidden;
			avatarTrigger.setAttribute('aria-expanded', String(!menu.hidden));
		}

		return;
	}

	if (!event.target.closest('[data-avatar-menu]')) {
		document.querySelectorAll('[data-avatar-menu]').forEach((menu) => {
			if (menu instanceof HTMLElement) {
				menu.hidden = true;
			}
		});
		document.querySelectorAll('[data-avatar-trigger]').forEach((trigger) => {
			if (trigger instanceof HTMLButtonElement) {
				trigger.setAttribute('aria-expanded', 'false');
			}
		});
	}

	const cancelButton = event.target.closest('.delete-cancel');
	if (cancelButton instanceof HTMLElement) {
		const modal = cancelButton.closest('.delete-confirm-modal');
		if (modal instanceof HTMLElement) {
			event.preventDefault();
			event.stopPropagation();
			modal.hidden = true;
		}
		return;
	}

	const modalBackdrop = event.target.closest('.delete-confirm-modal');
	if (modalBackdrop instanceof HTMLElement && event.target === modalBackdrop) {
		event.preventDefault();
		modalBackdrop.hidden = true;
		return;
	}

	const button = event.target.closest('[data-comment-toggle]');

	if (!(button instanceof HTMLButtonElement)) {
		return;
	}

	const formId = button.getAttribute('aria-controls');
	const form = formId ? document.getElementById(formId) : null;

	if (!(form instanceof HTMLFormElement)) {
		return;
	}

	const isOpen = button.getAttribute('aria-expanded') === 'true';

	button.setAttribute('aria-expanded', String(!isOpen));
	form.hidden = isOpen;

	const label = isOpen ? 'Write a comment' : 'Close comment form';

	button.setAttribute('aria-label', label);
	button.setAttribute('title', label);

	if (!isOpen) {
		form.querySelector('textarea')?.focus();
	}
});

document.addEventListener('change', (event) => {
	if (!(event.target instanceof HTMLInputElement) || !event.target.matches('[data-avatar-upload]')) {
		return;
	}

	if (event.target.files?.length) {
		event.target.form?.requestSubmit();
	}
});

document.addEventListener('submit', (event) => {
	if (!(event.target instanceof HTMLFormElement)) {
		return;
	}

	const reactionForm = event.target.closest('[data-reaction-form]');
	if (reactionForm instanceof HTMLFormElement) {
		event.preventDefault();
		void submitReaction(reactionForm);
		return;
	}

	const deleteForm = event.target.closest('.delete-confirm-form');
	if (!(deleteForm instanceof HTMLFormElement)) {
		return;
	}

	const modal = deleteForm.closest('.delete-confirm-modal');
	if (modal instanceof HTMLElement) {
		modal.hidden = true;
	}
});

async function submitReaction(form) {
	const button = form.querySelector('button[type="submit"]');
	const postActions = form.closest('.post-actions');
	const reaction = form.querySelector('input[name="reaction"]')?.value;

	if (!(button instanceof HTMLButtonElement) || !(postActions instanceof HTMLElement) || !reaction) {
		return;
	}

	button.disabled = true;
	postActions.setAttribute('aria-busy', 'true');

	try {
		const response = await fetch(form.action, {
			method: 'POST',
			headers: {
				Accept: 'application/json',
				'X-Requested-With': 'XMLHttpRequest',
			},
			body: new FormData(form),
		});

		if (!response.ok) {
			throw new Error('Reaction request failed.');
		}

		const result = await response.json();
		postActions.querySelector('[data-reaction-count="like"]')?.replaceChildren(String(result.likes_count));
		postActions.querySelector('[data-reaction-count="dislike"]')?.replaceChildren(String(result.dislikes_count));

		postActions.querySelectorAll('[data-reaction-form]').forEach((reactionForm) => {
			const reactionButton = reactionForm.querySelector('button[type="submit"]');
			const reactionInput = reactionForm.querySelector('input[name="reaction"]');
			const isActive = reactionInput?.value === result.reaction;

			if (reactionButton instanceof HTMLButtonElement) {
				reactionButton.classList.toggle('is-active', isActive);
				reactionButton.setAttribute('aria-pressed', String(isActive));
			}
		});
	} catch {
		form.submit();
	} finally {
		button.disabled = false;
		postActions.removeAttribute('aria-busy');
	}
}

document.addEventListener('keydown', (event) => {
	if (event.key !== 'Escape') {
		return;
	}

	const avatarMenu = document.querySelector('[data-avatar-menu]:not([hidden])');
	if (avatarMenu instanceof HTMLElement) {
		avatarMenu.hidden = true;
		avatarMenu.parentElement?.querySelector('[data-avatar-trigger]')?.setAttribute('aria-expanded', 'false');
		return;
	}

	const openModal = document.querySelector('.delete-confirm-modal:not([hidden])');
	if (openModal instanceof HTMLElement) {
		openModal.hidden = true;
		return;
	}

	document.querySelectorAll('.post-menu[open]').forEach((menu) => {
		menu.removeAttribute('open');
		menu.querySelector('summary')?.focus();
	});
});
