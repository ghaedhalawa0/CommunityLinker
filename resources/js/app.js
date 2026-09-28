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
	if (deleteForm instanceof HTMLFormElement) {
		const modal = deleteForm.closest('.delete-confirm-modal');
		if (modal instanceof HTMLElement) {
			modal.hidden = true;
		}
		return;
	}

	const messageDeleteForm = event.target.closest('[data-message-delete-form]');
	if (messageDeleteForm instanceof HTMLFormElement) {
		event.preventDefault();
		void submitMessageDelete(messageDeleteForm);
		return;
	}

	const messageEditForm = event.target.closest('[data-message-edit-form]');
	if (messageEditForm instanceof HTMLFormElement) {
		event.preventDefault();
		void submitMessageEdit(messageEditForm);
		return;
	}

	const forwardForm = event.target.closest('[data-forward-form]');
	if (forwardForm instanceof HTMLFormElement) {
		event.preventDefault();
		void submitForwardMessage(forwardForm);
	}
});

async function submitMessageDelete(form) {
	const button = form.querySelector('button[type="submit"]');
	const row = form.closest('[data-message-id]');
	const status = form.closest('[data-chat]')?.querySelector('[data-chat-status]');
	if (!(button instanceof HTMLButtonElement) || !(row instanceof HTMLElement)) {
		return;
	}

	button.disabled = true;
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
			throw new Error('This message could not be deleted.');
		}

		row.remove();
		if (status instanceof HTMLElement) {
			status.textContent = 'Message deleted.';
		}
	} catch (error) {
		button.disabled = false;
		if (status instanceof HTMLElement) {
			status.textContent = error instanceof Error ? error.message : 'This message could not be deleted.';
		}
	}
}

async function submitMessageEdit(form) {
	const button = form.querySelector('button[type="submit"]');
	const row = form.closest('[data-message-id]');
	const bodyField = form.querySelector('textarea[name="body"]');
	const status = form.closest('[data-chat]')?.querySelector('[data-chat-status]');
	if (!(button instanceof HTMLButtonElement) || !(row instanceof HTMLElement) || !(bodyField instanceof HTMLTextAreaElement)) {
		return;
	}

	button.disabled = true;
	try {
		const response = await fetch(form.action, {
			method: 'POST',
			headers: {
				Accept: 'application/json',
				'X-Requested-With': 'XMLHttpRequest',
			},
			body: new FormData(form),
		});
		const result = await response.json();
		if (!response.ok) {
			throw new Error(result.message || 'This message could not be updated.');
		}

		const bodyText = row.querySelector('[data-message-body-text]');
		if (bodyText instanceof HTMLElement) {
			bodyText.textContent = result.body;
		}
		row.dataset.messageBody = result.body;
		form.hidden = true;
		if (bodyText instanceof HTMLElement) {
			bodyText.hidden = false;
		}
		if (status instanceof HTMLElement) {
			status.textContent = 'Message updated.';
		}
	} catch (error) {
		if (status instanceof HTMLElement) {
			status.textContent = error instanceof Error ? error.message : 'This message could not be updated.';
		}
	} finally {
		button.disabled = false;
	}
}

async function submitForwardMessage(form) {
	const button = form.querySelector('button[type="submit"]');
	const dialog = form.closest('dialog');
	const status = form.closest('[data-chat]')?.querySelector('[data-chat-status]');
	const selectedRecipient = form.querySelector('[data-forward-recipient]:checked');
	const recipientName = selectedRecipient?.closest('[data-forward-recipient-option]')?.querySelector('[data-forward-recipient-name]')?.textContent?.trim();
	if (!(button instanceof HTMLButtonElement) || !(dialog instanceof HTMLDialogElement)) {
		return;
	}

	button.disabled = true;
	try {
		const response = await fetch(form.action, {
			method: 'POST',
			headers: {
				Accept: 'application/json',
				'X-Requested-With': 'XMLHttpRequest',
			},
			body: new FormData(form),
		});
		const result = await response.json();
		if (!response.ok) {
			throw new Error(result.message || 'This message could not be forwarded.');
		}

		dialog.close();
		form.reset();
		if (status instanceof HTMLElement) {
			status.textContent = recipientName ? `Message forwarded to ${recipientName}.` : 'Message forwarded.';
		}
	} catch (error) {
		if (status instanceof HTMLElement) {
			status.textContent = error instanceof Error ? error.message : 'This message could not be forwarded.';
		}
	} finally {
		button.disabled = false;
	}
}

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

	const openMessageMenu = document.querySelector('[data-message-menu-panel]:not([hidden])');
	if (openMessageMenu instanceof HTMLElement) {
		openMessageMenu.hidden = true;
		openMessageMenu.closest('[data-message-menu]')?.querySelector('[data-message-menu-trigger]')?.focus();
		return;
	}

	document.querySelectorAll('.post-menu[open]').forEach((menu) => {
		menu.removeAttribute('open');
		menu.querySelector('summary')?.focus();
	});
});

const messagesLink = document.querySelector('[data-messages-link]');

if (messagesLink instanceof HTMLAnchorElement) {
	function updateUnreadIndicator(hasUnreadMessages) {
		let indicator = messagesLink.querySelector('[data-unread-indicator]');

		if (hasUnreadMessages && !indicator) {
			indicator = document.createElement('span');
			indicator.className = 'nav-unread-dot';
			indicator.dataset.unreadIndicator = '';
			indicator.setAttribute('role', 'img');
			indicator.setAttribute('aria-label', 'Unread messages');
			messagesLink.append(indicator);
		} else if (!hasUnreadMessages) {
			indicator?.remove();
		}
	}

	async function refreshUnreadIndicator() {
		if (document.hidden) {
			return;
		}

		try {
			const response = await fetch(messagesLink.dataset.unreadUrl, {
				headers: {
					Accept: 'application/json',
					'X-Requested-With': 'XMLHttpRequest',
				},
			});

			if (response.ok) {
				const result = await response.json();
				updateUnreadIndicator(result.has_unread_messages);
			}
		} catch {
			return;
		}
	}

	window.setInterval(() => void refreshUnreadIndicator(), 10000);
	document.addEventListener('visibilitychange', () => {
		if (!document.hidden) {
			void refreshUnreadIndicator();
		}
	});
}

const chatPage = document.querySelector('[data-chat]');
const messageList = chatPage?.querySelector('[data-message-list]');
const messageForm = chatPage?.querySelector('[data-message-form]');

if (chatPage instanceof HTMLElement && messageList instanceof HTMLOListElement && messageForm instanceof HTMLFormElement) {
	let latestMessageId = Number(messageList.dataset.afterId || 0);
	let latestUpdatedAt = messageList.dataset.updatedAfter || new Date(0).toISOString();
	let isPolling = false;
	const currentUserId = Number(chatPage.dataset.currentUserId);
	const chatHistory = chatPage.querySelector('.chat-history');
	const messageField = messageForm.querySelector('textarea[name="body"]');
	const emojiTrigger = messageForm.querySelector('[data-emoji-trigger]');
	const emojiPicker = messageForm.querySelector('[data-emoji-picker]');
	const forwardDialog = chatPage.querySelector('[data-forward-dialog]');
	const forwardForm = chatPage.querySelector('[data-forward-form]');
	const forwardSearch = chatPage.querySelector('[data-forward-search]');
	const forwardSubmit = chatPage.querySelector('[data-forward-submit]');
	const forwardMessagePreview = chatPage.querySelector('[data-forward-message-preview]');
	const forwardRecipientList = chatPage.querySelector('[data-forward-recipient-list]');
	const forwardRecipientCount = chatPage.querySelector('[data-forward-recipient-count]');
	const forwardEmpty = chatPage.querySelector('[data-forward-empty]');
	const forwardNoResults = chatPage.querySelector('[data-forward-no-results]');
	const replyIdField = messageForm.querySelector('[data-reply-to-message-id]');
	const replyPreview = messageForm.querySelector('[data-reply-preview]');
	let forwardSearchTimer;
	let forwardSearchController;
	let forwardSearchRequest = 0;
	const iconPaths = {
		edit: ['M12 20h9', 'M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z'],
		reply: ['m9 14-5-5 5-5', 'M4 9h10a5 5 0 0 1 0 10h-1'],
		forward: ['m15 14 5-5-5-5', 'M20 9H10a5 5 0 0 0 0 10h1'],
		delete: ['M3 6h18', 'M8 6V4h8v2m-9 0 1 14h8l1-14M10 10v6m4-6v6'],
		save: ['m5 12 4 4L19 6'],
		cancel: ['m18 6-12 12M6 6l12 12'],
	};

	function createActionButton(action, label, extraClass = '') {
		const button = document.createElement('button');
		button.className = `chat-action-button${extraClass ? ` ${extraClass}` : ''}`;
		button.type = 'button';
		button.dataset.messageAction = action;
		button.setAttribute('aria-label', label);
		button.title = label;

		const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
		icon.setAttribute('viewBox', '0 0 24 24');
		icon.setAttribute('aria-hidden', 'true');
		iconPaths[action].forEach((pathData) => {
			const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
			path.setAttribute('d', pathData);
			icon.append(path);
		});
		button.append(icon);
		return button;
	}

	function createMenuItem(action, label, isDanger = false) {
		const button = document.createElement('button');
		button.className = `chat-message-menu-item${isDanger ? ' chat-message-menu-item-danger' : ''}`;
		button.type = 'button';
		button.dataset.messageAction = action;
		button.setAttribute('aria-label', label);

		const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
		icon.setAttribute('viewBox', '0 0 24 24');
		icon.setAttribute('aria-hidden', 'true');
		iconPaths[action].forEach((pathData) => {
			const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
			path.setAttribute('d', pathData);
			icon.append(path);
		});

		const text = document.createElement('span');
		text.textContent = label;
		button.append(icon, text);
		return button;
	}

	function appendHiddenInput(form, name, value) {
		const input = document.createElement('input');
		input.type = 'hidden';
		input.name = name;
		input.value = value;
		form.append(input);
	}

	function createEditForm(row) {
		const form = document.createElement('form');
		form.className = 'chat-message-edit-form';
		form.method = 'POST';
		form.action = chatPage.dataset.updateUrlTemplate.replace('MESSAGE_ID', row.dataset.messageId);
		form.dataset.messageEditForm = '';
		form.hidden = true;
		appendHiddenInput(form, '_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
		appendHiddenInput(form, '_method', 'PATCH');

		const textarea = document.createElement('textarea');
		textarea.name = 'body';
		textarea.rows = 2;
		textarea.maxLength = 2000;
		textarea.required = true;
		textarea.value = row.dataset.messageBody;
		form.append(textarea);

		const actions = document.createElement('span');
		actions.className = 'chat-edit-actions';
		const saveButton = createActionButton('save', 'Save edit');
		saveButton.type = 'submit';
		delete saveButton.dataset.messageAction;
		const cancelButton = createActionButton('cancel', 'Cancel edit');
		cancelButton.dataset.messageEditCancel = '';
		actions.append(saveButton, cancelButton);
		form.append(actions);
		row.querySelector('.chat-message-bubble [data-message-body-text]')?.after(form);
		return form;
	}

	function clearReplySelection() {
		if (replyIdField instanceof HTMLInputElement) {
			replyIdField.value = '';
		}
		if (replyPreview instanceof HTMLElement) {
			replyPreview.hidden = true;
			replyPreview.querySelector('[data-reply-sender]')?.replaceChildren();
			replyPreview.querySelector('[data-reply-body]')?.replaceChildren();
		}
	}

	function clearForwardRecipientResults() {
		window.clearTimeout(forwardSearchTimer);
		forwardSearchController?.abort();
		forwardSearchRequest += 1;
		if (forwardRecipientList instanceof HTMLElement) {
			forwardRecipientList.replaceChildren();
		}
		if (forwardRecipientCount instanceof HTMLElement) {
			forwardRecipientCount.textContent = '0';
		}
		if (forwardNoResults instanceof HTMLElement) {
			forwardNoResults.hidden = true;
		}
		if (forwardEmpty instanceof HTMLElement) {
			forwardEmpty.textContent = 'Search by name or username to find recipients.';
			forwardEmpty.hidden = false;
		}
		updateForwardSubmit();
	}

	function createForwardRecipientOption(recipient) {
		const option = document.createElement('label');
		option.className = 'chat-forward-recipient';
		option.dataset.forwardRecipientOption = '';

		const radio = document.createElement('input');
		radio.type = 'radio';
		radio.name = 'recipient_id';
		radio.value = String(recipient.id);
		radio.required = true;
		radio.dataset.forwardRecipient = '';
		option.append(radio);

		const palette = ['avatar-fern', 'avatar-coral', 'avatar-sky', 'avatar-gold'];
		const avatar = document.createElement('span');
		avatar.className = `avatar avatar-sm ${palette[recipient.id % palette.length]}`;
		avatar.setAttribute('aria-hidden', 'true');
		if (recipient.avatar_url) {
			const image = document.createElement('img');
			image.className = 'avatar-image';
			image.src = recipient.avatar_url;
			image.alt = '';
			avatar.append(image);
		} else {
			const initials = recipient.name
				.trim()
				.split(/\s+/)
				.slice(0, 2)
				.map((part) => part.charAt(0).toLocaleUpperCase())
				.join('');
			avatar.textContent = initials;
		}
		option.append(avatar);

		const copy = document.createElement('span');
		copy.className = 'chat-forward-recipient-copy';
		const name = document.createElement('strong');
		name.dataset.forwardRecipientName = '';
		name.textContent = recipient.name;
		const username = document.createElement('span');
		username.textContent = `@${recipient.username}`;
		copy.append(name, username);
		option.append(copy);
		return option;
	}

	async function searchForwardRecipients(query) {
		forwardSearchController?.abort();
		const controller = new AbortController();
		forwardSearchController = controller;
		const requestId = ++forwardSearchRequest;
		const searchUrl = new URL(forwardSearch.dataset.searchUrl, window.location.origin);
		searchUrl.searchParams.set('query', query);

		try {
			const response = await fetch(searchUrl, {
				headers: {
					Accept: 'application/json',
					'X-Requested-With': 'XMLHttpRequest',
				},
				signal: controller.signal,
			});
			if (!response.ok) {
				throw new Error('Recipients could not be loaded. Try again.');
			}

			const result = await response.json();
			if (requestId !== forwardSearchRequest) {
				return;
			}

			forwardRecipientList.replaceChildren(...result.recipients.map(createForwardRecipientOption));
			forwardRecipientCount.textContent = String(result.recipients.length);
			forwardEmpty.hidden = true;
			forwardNoResults.hidden = result.recipients.length > 0;
		} catch (error) {
			if (error instanceof DOMException && error.name === 'AbortError') {
				return;
			}
			if (forwardEmpty instanceof HTMLElement) {
				forwardEmpty.textContent = error instanceof Error ? error.message : 'Recipients could not be loaded. Try again.';
				forwardEmpty.hidden = false;
			}
		} finally {
			updateForwardSubmit();
		}
	}

	function handleForwardSearchInput() {
		if (!(forwardSearch instanceof HTMLInputElement) || !(forwardRecipientList instanceof HTMLElement)) {
			return;
		}

		window.clearTimeout(forwardSearchTimer);
		clearForwardRecipientResults();
		const query = forwardSearch.value.trim();
		if (query.length < 2) {
			if (forwardEmpty instanceof HTMLElement && query.length > 0) {
				forwardEmpty.textContent = 'Enter at least 2 characters to search.';
			}
			return;
		}

		forwardEmpty.hidden = true;
		forwardSearchTimer = window.setTimeout(() => {
			void searchForwardRecipients(query);
		}, 250);
	}

	function updateForwardSubmit() {
		if (!(forwardForm instanceof HTMLFormElement) || !(forwardSubmit instanceof HTMLButtonElement)) {
			return;
		}

		const selectedRecipient = forwardForm.querySelector('[data-forward-recipient]:checked');
		const recipientName = selectedRecipient
			?.closest('[data-forward-recipient-option]')
			?.querySelector('[data-forward-recipient-name]')
			?.textContent?.trim();
		forwardSubmit.disabled = !recipientName;
		const submitLabel = forwardSubmit.querySelector('[data-forward-submit-label]');
		if (submitLabel instanceof HTMLElement) {
			submitLabel.textContent = recipientName ? `Forward to ${recipientName}` : 'Choose a recipient';
		}
	}

	function prepareForwardDialog(messageBody) {
		if (forwardForm instanceof HTMLFormElement) {
			forwardForm.reset();
		}
		if (forwardMessagePreview instanceof HTMLElement) {
			forwardMessagePreview.querySelector('[data-forward-message-preview]')?.replaceChildren(messageBody);
			forwardMessagePreview.hidden = false;
		}
		if (forwardSearch instanceof HTMLInputElement) {
			forwardSearch.value = '';
		}
		clearForwardRecipientResults();
	}

	document.addEventListener('click', (event) => {
		if (!(event.target instanceof Element)) {
			return;
		}

		document.querySelectorAll('[data-message-menu-panel]:not([hidden])').forEach((panel) => {
			if (!panel.closest('[data-message-menu]')?.contains(event.target)) {
				panel.hidden = true;
				panel.closest('[data-message-menu]')?.querySelector('[data-message-menu-trigger]')?.setAttribute('aria-expanded', 'false');
			}
		});

		if (event.target.closest('[data-reply-cancel]')) {
			clearReplySelection();
			messageField?.focus();
			return;
		}

		if (event.target.closest('[data-forward-cancel]') && forwardDialog instanceof HTMLDialogElement) {
			forwardDialog.close();
		}
	});

	forwardSearch?.addEventListener('input', handleForwardSearchInput);
	forwardDialog?.addEventListener('close', clearForwardRecipientResults);
	forwardForm?.addEventListener('change', (event) => {
		if (event.target instanceof HTMLInputElement && event.target.matches('[data-forward-recipient]')) {
			updateForwardSubmit();
		}
	});

	if (messageField instanceof HTMLTextAreaElement && emojiTrigger instanceof HTMLButtonElement && emojiPicker instanceof HTMLElement) {
		messageField.addEventListener('keydown', (event) => {
			if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
				event.preventDefault();
				messageForm.requestSubmit();
			}
		});

		emojiTrigger.addEventListener('click', () => {
			emojiPicker.hidden = !emojiPicker.hidden;
			emojiTrigger.setAttribute('aria-expanded', String(!emojiPicker.hidden));
			if (!emojiPicker.hidden) {
				emojiPicker.querySelector('button')?.focus();
			}
		});

		emojiPicker.addEventListener('click', (event) => {
			if (!(event.target instanceof Element)) {
				return;
			}

			const emojiButton = event.target.closest('[data-emoji-value]');
			const emoji = emojiButton?.getAttribute('data-emoji-value');
			if (!emoji) {
				return;
			}

			const selectionStart = messageField.selectionStart;
			const selectionEnd = messageField.selectionEnd;
			const nextLength = messageField.value.length - (selectionEnd - selectionStart) + emoji.length;
			if (nextLength > messageField.maxLength) {
				return;
			}

			messageField.setRangeText(emoji, selectionStart, selectionEnd, 'end');
			messageField.focus();
		});

		emojiPicker.addEventListener('keydown', (event) => {
			if (event.key === 'Escape') {
				emojiPicker.hidden = true;
				emojiTrigger.setAttribute('aria-expanded', 'false');
				emojiTrigger.focus();
			}
		});
	}

	messageList.addEventListener('click', (event) => {
		if (!(event.target instanceof Element)) {
			return;
		}

		const menuTrigger = event.target.closest('[data-message-menu-trigger]');
		if (menuTrigger instanceof HTMLButtonElement) {
			const menu = menuTrigger.closest('[data-message-menu]');
			const panel = menu?.querySelector('[data-message-menu-panel]');
			if (panel instanceof HTMLElement) {
				const shouldOpen = panel.hidden;
				messageList.querySelectorAll('[data-message-menu-panel]:not([hidden])').forEach((otherPanel) => {
					otherPanel.hidden = true;
					otherPanel.closest('[data-message-menu]')?.querySelector('[data-message-menu-trigger]')?.setAttribute('aria-expanded', 'false');
				});
				panel.hidden = !shouldOpen;
				menuTrigger.setAttribute('aria-expanded', String(shouldOpen));
				if (shouldOpen && chatHistory instanceof HTMLElement) {
					const historyBounds = chatHistory.getBoundingClientRect();
					const triggerBounds = menuTrigger.getBoundingClientRect();
					panel.classList.toggle(
						'chat-menu-opens-up',
						historyBounds.bottom - triggerBounds.bottom < panel.getBoundingClientRect().height + 8,
					);
				}
			}
			return;
		}

		const cancelEditButton = event.target.closest('[data-message-edit-cancel]');
		if (cancelEditButton) {
			const row = cancelEditButton.closest('[data-message-id]');
			const editForm = cancelEditButton.closest('[data-message-edit-form]');
			const bodyText = row?.querySelector('[data-message-body-text]');
			if (editForm instanceof HTMLFormElement && bodyText instanceof HTMLElement) {
				editForm.hidden = true;
				bodyText.hidden = false;
				editForm.querySelector('textarea')?.blur();
			}
			return;
		}

		const actionButton = event.target.closest('[data-message-action]');
		const row = actionButton?.closest('[data-message-id]');
		if (!(row instanceof HTMLElement)) {
			return;
		}

		const action = actionButton.getAttribute('data-message-action');
		const menuPanel = row.querySelector('[data-message-menu-panel]');
		if (menuPanel instanceof HTMLElement) {
			menuPanel.hidden = true;
			row.querySelector('[data-message-menu-trigger]')?.setAttribute('aria-expanded', 'false');
		}
		if (action === 'edit') {
			const bodyText = row.querySelector('[data-message-body-text]');
			const editForm = row.querySelector('[data-message-edit-form]') || createEditForm(row);
			if (bodyText instanceof HTMLElement && editForm instanceof HTMLFormElement) {
				bodyText.hidden = true;
				editForm.hidden = false;
				editForm.querySelector('textarea')?.focus();
			}
		} else if (action === 'reply' && replyIdField instanceof HTMLInputElement && replyPreview instanceof HTMLElement) {
			replyIdField.value = row.dataset.messageId;
			replyPreview.querySelector('[data-reply-sender]')?.replaceChildren(row.dataset.messageSender);
			replyPreview.querySelector('[data-reply-body]')?.replaceChildren(row.dataset.messageBody);
			replyPreview.hidden = false;
			messageField?.focus();
		} else if (action === 'forward' && forwardDialog instanceof HTMLDialogElement && forwardForm instanceof HTMLFormElement) {
			forwardForm.action = chatPage.dataset.forwardUrlTemplate.replace('MESSAGE_ID', row.dataset.messageId);
			prepareForwardDialog(row.dataset.messageBody);
			forwardDialog.showModal();
			forwardSearch?.focus();
		}
	});

	function appendMessage(message) {
		if (!Number.isSafeInteger(message.id)) {
			return;
		}
		const existingRow = messageList.querySelector(`[data-message-id="${message.id}"]`);
		if (existingRow instanceof HTMLElement) {
			const bodyText = existingRow.querySelector('[data-message-body-text]');
			if (bodyText instanceof HTMLElement) {
				bodyText.textContent = message.body;
				existingRow.dataset.messageBody = message.body;
				const editField = existingRow.querySelector('[data-message-edit-form] textarea');
				if (editField instanceof HTMLTextAreaElement) {
					editField.value = message.body;
				}
			}
			return;
		}

		const isOwnMessage = Number(message.sender_id) === currentUserId;
		const row = document.createElement('li');
		row.className = `chat-message-row${isOwnMessage ? ' is-own-message' : ''}`;
		row.dataset.messageId = String(message.id);
		row.dataset.messageBody = message.body;
		row.dataset.messageSender = message.sender_name;

		const stack = document.createElement('div');
		stack.className = 'chat-message-stack';
		const bubble = document.createElement('article');
		bubble.className = 'chat-message-bubble';

		if (!isOwnMessage) {
			const sender = document.createElement('strong');
			sender.textContent = message.sender_name;
			bubble.append(sender);
		}
		if (message.forwarded_from) {
			const forwardedLabel = document.createElement('small');
			forwardedLabel.className = 'chat-forwarded-label';
			forwardedLabel.textContent = `Forwarded from ${message.forwarded_from.sender_name}`;
			bubble.append(forwardedLabel);
		}
		if (message.reply_to) {
			const replyQuote = document.createElement('blockquote');
			replyQuote.className = 'chat-reply-quote';
			const replySender = document.createElement('strong');
			replySender.textContent = message.reply_to.sender_name;
			const replyBody = document.createElement('span');
			replyBody.textContent = message.reply_to.body;
			replyQuote.append(replySender, replyBody);
			bubble.append(replyQuote);
		}

		const body = document.createElement('p');
		body.dataset.messageBodyText = '';
		body.textContent = message.body;
		bubble.append(body);

		const time = document.createElement('time');
		time.dateTime = message.created_at;
		time.textContent = new Date(message.created_at).toLocaleTimeString([], {
			hour: 'numeric',
			minute: '2-digit',
		});
		const meta = document.createElement('div');
		meta.className = 'chat-message-meta';
		meta.append(time);
		bubble.append(meta);

		const menu = document.createElement('div');
		menu.className = 'chat-message-menu';
		menu.dataset.messageMenu = '';
		const menuId = `message-actions-${message.id}`;
		const menuTrigger = document.createElement('button');
		menuTrigger.className = 'chat-more-trigger';
		menuTrigger.type = 'button';
		menuTrigger.textContent = '•••';
		menuTrigger.setAttribute('aria-label', 'Message options');
		menuTrigger.setAttribute('aria-controls', menuId);
		menuTrigger.setAttribute('aria-expanded', 'false');
		menuTrigger.title = 'Message options';
		menuTrigger.dataset.messageMenuTrigger = '';
		menu.append(menuTrigger);

		const menuPanel = document.createElement('div');
		menuPanel.className = 'chat-message-menu-panel';
		menuPanel.id = menuId;
		menuPanel.dataset.messageMenuPanel = '';
		menuPanel.hidden = true;
		if (isOwnMessage) {
			menuPanel.append(createMenuItem('edit', 'Edit message'));
		}
		menuPanel.append(
			createMenuItem('reply', 'Reply'),
			createMenuItem('forward', 'Forward'),
		);
		if (isOwnMessage) {
			const deleteUrl = chatPage.dataset.deleteUrlTemplate.replace('MESSAGE_ID', String(message.id));
			const deleteForm = document.createElement('form');
			deleteForm.className = 'chat-message-delete-form';
			deleteForm.method = 'POST';
			deleteForm.action = deleteUrl;
			deleteForm.dataset.messageDeleteForm = '';

			appendHiddenInput(deleteForm, '_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
			appendHiddenInput(deleteForm, '_method', 'DELETE');
			const deleteButton = createMenuItem('delete', 'Delete message', true);
			deleteButton.type = 'submit';
			deleteButton.removeAttribute('data-message-action');
			deleteForm.append(deleteButton);
			menuPanel.append(deleteForm);
		}
		menu.append(menuPanel);
		stack.append(bubble, menu);
		row.append(stack);
		messageList.append(row);

		latestMessageId = Math.max(latestMessageId, message.id);
		messageList.dataset.afterId = String(latestMessageId);
		chatPage.querySelector('[data-chat-empty]')?.remove();
		if (chatHistory instanceof HTMLElement) {
			chatHistory.scrollTop = chatHistory.scrollHeight;
		}
	}

	async function receiveMessages() {
		if (document.hidden || isPolling) {
			return;
		}

		isPolling = true;
		const updatesUrl = new URL(messageList.dataset.updatesUrl, window.location.origin);
		updatesUrl.searchParams.set('after', String(latestMessageId));
		updatesUrl.searchParams.set('updated_after', latestUpdatedAt);

		try {
			const response = await fetch(updatesUrl, {
				headers: {
					Accept: 'application/json',
					'X-Requested-With': 'XMLHttpRequest',
				},
			});

			if (!response.ok) {
				return;
			}

			const result = await response.json();
			result.messages.forEach(appendMessage);
			result.messages.forEach((message) => {
				if (message.updated_at && message.updated_at > latestUpdatedAt) {
					latestUpdatedAt = message.updated_at;
				}
			});
			messageList.dataset.updatedAfter = latestUpdatedAt;
		} catch {
			return;
		} finally {
			isPolling = false;
		}
	}

	messageForm.addEventListener('submit', async (event) => {
		event.preventDefault();
		const submitButton = messageForm.querySelector('button[type="submit"]');
		const status = chatPage.querySelector('[data-chat-status]');
		if (!(submitButton instanceof HTMLButtonElement)) {
			return;
		}

		submitButton.disabled = true;
		try {
			const response = await fetch(messageForm.action, {
				method: 'POST',
				headers: {
					Accept: 'application/json',
					'X-Requested-With': 'XMLHttpRequest',
				},
				body: new FormData(messageForm),
			});
			const result = await response.json();
			if (!response.ok) {
				throw new Error(result.message || 'Your message could not be sent.');
			}

			appendMessage(result);
			latestUpdatedAt = result.updated_at > latestUpdatedAt ? result.updated_at : latestUpdatedAt;
			messageList.dataset.updatedAfter = latestUpdatedAt;
			messageForm.reset();
			clearReplySelection();
			if (emojiPicker instanceof HTMLElement && emojiTrigger instanceof HTMLButtonElement) {
				emojiPicker.hidden = true;
				emojiTrigger.setAttribute('aria-expanded', 'false');
			}
			if (status instanceof HTMLElement) {
				status.textContent = 'Message sent.';
			}
		} catch (error) {
			if (status instanceof HTMLElement) {
				status.textContent = error instanceof Error ? error.message : 'Your message could not be sent.';
			}
		} finally {
			submitButton.disabled = false;
			messageForm.querySelector('textarea')?.focus();
		}
	});

	if (chatHistory instanceof HTMLElement) {
		chatHistory.scrollTop = chatHistory.scrollHeight;
	}
	window.setInterval(() => void receiveMessages(), 4000);
	document.addEventListener('visibilitychange', () => {
		if (!document.hidden) {
			void receiveMessages();
		}
	});
}
