


// Quote form controller. Loaded with defer after the shared stylesheet.
(() => {
  'use strict';

  const root = document.getElementById('tn-quote');
  if (!root) return;
  const form = root.querySelector('form');
  let activeReviewEdit = null;
  let detailsExpanded = false;
  let projectDetailsIncluded = false;
  const productDetailsIncluded = new Set();
  const products = () => [...root.querySelectorAll('[name="product"]:checked')].map(input => input.value);
  const error = root.querySelector('#form-error');
  const confirmation = root.querySelector('#confirmation');
  const fileInput = root.querySelector('#drawing-files');
  const dropZone = root.querySelector('#drawing-drop-zone');
  const attachmentList = root.querySelector('#drawing-attachments');
  const uploadStatus = root.querySelector('#upload-status');
  let drawings = [];
  // Drawing attachments
  function showDrawings() {
    attachmentList.replaceChildren();
    drawings.forEach((file,index) => {
      const item = document.createElement('li');
      const name = document.createElement('span'); name.className = 'file-name'; name.textContent = file.name;
      const size = document.createElement('small'); size.textContent = file.size >= 1048576 ? (file.size/1048576).toFixed(1) + ' MB' : Math.max(1,Math.round(file.size/1024)) + ' KB'; name.append(size);
      const remove = document.createElement('button'); remove.type = 'button'; remove.textContent = 'Remove'; remove.setAttribute('aria-label','Remove ' + file.name);
      remove.onclick = () => {drawings.splice(index,1); showDrawings(); renderReview(); confirmation.hidden = true; uploadStatus.textContent = file.name + ' removed.';};
      item.append(name,remove); attachmentList.append(item);
    });
  }
  function addDrawings(files) {
    let added = 0, rejected = 0;
    [...files].forEach(file => {
      if (!/\.(pdf|png|jpe?g)$/i.test(file.name)) {rejected++; return;}
      if (!drawings.some(existing => existing.name === file.name && existing.size === file.size && existing.lastModified === file.lastModified)) {drawings.push(file); added++;}
    });
    showDrawings(); renderReview(); confirmation.hidden = true;
    uploadStatus.textContent = added + ' file' + (added === 1 ? '' : 's') + ' added. ' + drawings.length + ' attached.' + (rejected ? ' Unsupported files skipped; choose PDF, JPG or PNG.' : '');
    fileInput.value = '';
  }
  root.querySelector('#choose-drawings').onclick = () => fileInput.click();
  fileInput.addEventListener('change', () => addDrawings(fileInput.files));
  let dragDepth = 0;
  dropZone.addEventListener('dragenter', event => {event.preventDefault(); dragDepth++; dropZone.classList.add('is-dragging');});
  dropZone.addEventListener('dragover', event => {event.preventDefault(); event.dataTransfer.dropEffect = 'copy';});
  dropZone.addEventListener('dragleave', () => {dragDepth--; if (dragDepth <= 0) {dragDepth = 0; dropZone.classList.remove('is-dragging');}});
  dropZone.addEventListener('drop', event => {event.preventDefault(); dragDepth = 0; dropZone.classList.remove('is-dragging'); addDrawings(event.dataTransfer.files);});
  document.addEventListener('dragover', event => {if ([...event.dataTransfer.types].includes('Files')) event.preventDefault();});
  document.addEventListener('drop', event => {if ([...event.dataTransfer.types].includes('Files')) event.preventDefault();});
  form.querySelectorAll('input:not([type="checkbox"]):not([type="radio"]),select,textarea').forEach((input, index) => {
    if (!input.id) input.id = 'quote-field-' + index;
    const label = input.closest('label');
    if (label) label.htmlFor = input.id;
  });
  // Review formatting and rendering
  function reviewValue(name) {
    const inputs = [...form.querySelectorAll('[name]')].filter(input => input.name === name && !input.matches(':disabled'));
    const input = inputs.find(input => !['radio','checkbox'].includes(input.type) || input.checked);
    return input ? (input.type === 'checkbox' ? 'Yes' : input.value.trim()) : '';
  }
  function reviewDate(value, withTime = false) {
    if (!value) return '';
    const date = new Date(value.includes('T') ? value : value + 'T12:00:00');
    if (Number.isNaN(date.getTime())) return value;
    return new Intl.DateTimeFormat('en-CA', {year:'numeric', month:'short', day:'numeric', ...(withTime ? {hour:'numeric', minute:'2-digit'} : {})}).format(date);
  }
  function reviewMeasure(name, unit) {
    const value = reviewValue(name);
    return value && /^\d+(\.\d+)?$/.test(value) ? value + ' ' + unit : value;
  }
  function renderReview() {
    if (activeReviewEdit) return;
    const review = root.querySelector('#review');
    review.replaceChildren();
    function group(title, rows, edit, empty = 'Not provided.', icon = '') {
      const section = document.createElement('section'); section.className = 'review-group';
      const header = document.createElement('div'); header.className = 'review-group-heading';
      const heading = document.createElement('h2'); heading.textContent = title;
      if (icon) {const image = document.createElement('img'); image.src = icon; image.alt = ''; image.className = 'review-product-icon' + (title === 'Building type' ? ' review-building-icon' : ''); heading.prepend(image);}
      const button = document.createElement('button'); button.type = 'button'; button.className = 'review-edit'; button.textContent = 'Edit'; button.setAttribute('aria-label','Edit ' + title); button.onclick = () => edit(section);
      header.append(heading,button); section.append(header);
      const populated = rows.filter(row => row[1]);
      if (populated.length) {
        const list = document.createElement('dl'); list.className = 'review-facts';
        populated.forEach(([label,value,valueIcon]) => {
          const row = document.createElement('div');
          if (/notes|options|drawings|locations/i.test(label)) row.className = 'review-fact-wide';
          const term = document.createElement('dt'); term.textContent = label;
          const definition = document.createElement('dd'); definition.textContent = value;
          if (valueIcon) {
            const image = document.createElement('img'); image.src = valueIcon; image.alt = ''; image.className = 'review-system-icon';
            const text = document.createElement('span'); text.textContent = value;
            definition.textContent = ''; definition.className = 'review-value-with-icon'; definition.append(image,text);
          }
          row.append(term,definition); list.append(row);
        }); section.append(list);
      } else {const message = document.createElement('p'); message.className = 'review-empty'; message.textContent = empty; section.append(message);}
      review.append(section);
    }
    group('Contact', [
      ['Name',reviewValue('Full name')],['Company',reviewValue('Company')],
      ['Email',reviewValue('Email')],['Contact number',reviewValue('Phone')],
      ['Best time to call',reviewDate(reviewValue('Call time'),true)]
    ], (section) => editReview('contact',section));
    const buildingIcon = {'Residential':'Images/residential.png','Commercial':'Images/commercial.png','Agricultural':'Images/farm.png'}[reviewValue('Building use')] || '';
    group('Building type', [['Type',reviewValue('Building use')]], (section) => editReview('building',section),'No building type selected.',buildingIcon);
    products().forEach(product => {
      let rows = [];
      let icon = '';
      if (product === 'Roof Trusses') {
        icon = 'Images/roof-system-icon.png';
        rows = [['Width / span',reviewMeasure('Roof width','ft')],['Length',reviewMeasure('Roof length','ft')],
          ['Roof pitch',reviewValue('Roof pitch') === 'Custom' ? reviewValue('Custom roof pitch') : reviewValue('Roof pitch')],
          ['Overhang',reviewValue('Overhang') === 'Custom' ? reviewMeasure('Custom overhang','in') : reviewValue('Overhang')],
          ['Heel height',reviewMeasure('Heel height','in')],['Beams required',reviewValue('Roof beams')],
          ['Additional options',['Gable Ends','Dropped Gables','Ladders','Hip End Style','4 ft Spacing','5 ft Spacing','Cantilever'].filter(name => reviewValue(name)).join(', ')],
          ['Cantilever details',reviewValue('Cantilever details')],['Roof notes',reviewValue('Roof notes')]];
      } else if (product === 'Wall Panels') {
        icon = 'Images/wall-system-icon.png';
        rows = [['Wall types',['Exterior Walls','Interior Walls'].filter(name => reviewValue(name)).join(', ')],
          ['Storeys',reviewValue('Storeys')],['Wall height',reviewValue('Wall height')],['Framing thickness',reviewValue('Wall thickness')],['Wall notes',reviewValue('Wall notes')]];
      } else {
        icon = 'Images/floor-system-icon.png';
        const systemIcon = {'Nordic (I-Joists)':'Images/i-joist.png','LVL (Laminated Beam)':'Images/laminated-beam.png','Tri-Floor (Open Web Trusses)':'Images/open-web-truss.png','Floor Cartridges':'Images/floor-cartridge.png'}[reviewValue('Floor type')] || '';
        rows = [['System type',reviewValue('Floor type'),systemIcon],['Depth details',reviewValue('Depth details')],
          ['Approximate span',reviewMeasure('Floor span','ft')],['Floor area',reviewMeasure('Floor area','sq ft')],['Locations / depth notes',reviewValue('Floor notes')]];
      }
      group(product,rows,(section) => editReview(product,section),'No additional specifications provided. We’ll confirm the details with you.',icon);
    });
    group('Project drawings', drawings.map((file,index) => ['Drawing ' + (index+1),file.name + ' (' + (file.size >= 1048576 ? (file.size/1048576).toFixed(1) + ' MB' : Math.max(1,Math.round(file.size/1024)) + ' KB') + ')']), (section) => editReview('drawings',section),'No drawings attached. You can add them before submitting.');
    group('Project & jobsite', [
      ['Construction start',reviewDate(reviewValue('Construction start'))],
      ['Site address',reviewValue('Site address')],['City',reviewValue('City')],['Province',reviewValue('Province')],['Lot number',reviewValue('Lot number')],
      ['Project notes',reviewValue('Project notes')],['Site / delivery notes',reviewValue('Site notes')]
    ], (section) => editReview('project',section),'No project or jobsite details provided.');
    group('Delivery & installation', [['Delivery needed',reviewValue('Delivery')],['Installation inquiry',reviewValue('Inquire about Installation') ? 'Requested' : 'Not requested']], (section) => editReview('services',section));
  }

  // Disclosure state
  function draw() {
    const wallProduct = root.querySelector('[name="product"][value="Wall Panels"]');
    const wallOptions = root.querySelector('#wall-scope-options');
    wallOptions.hidden = !wallProduct.checked;
    wallProduct.setAttribute('aria-expanded', String(wallProduct.checked));
    wallOptions.querySelectorAll('input').forEach(input => input.disabled = !wallProduct.checked);
    root.querySelectorAll('[data-custom],[data-reveal]').forEach(control => {
      const target = root.querySelector('#' + (control.dataset.custom || control.dataset.reveal));
      const active = control.dataset.custom ? control.value === 'Custom' : control.checked;
      target.hidden = !active; target.querySelector('input').disabled = !active;
    });
    root.querySelectorAll('[data-product-action]').forEach(action => {
      action.hidden = !products().includes(action.dataset.productAction);
    });
    root.querySelectorAll('[data-product]').forEach(panel => {
      const toggle = root.querySelector('[data-product-toggle="' + panel.dataset.product + '"]');
      const editingHere = activeReviewEdit && activeReviewEdit.target === panel.dataset.product;
      const expanded = products().includes(panel.dataset.product) && (editingHere || toggle.getAttribute('aria-expanded') === 'true');
      panel.hidden = !expanded;
      panel.disabled = !products().includes(panel.dataset.product) || (!editingHere && !productDetailsIncluded.has(panel.dataset.product));
    });
    renderReview();
    root.querySelector('#selection').hidden = products().length === 0;
    root.querySelector('#selection').textContent = products().length ? 'Your quote will include: ' + products().join(' + ') : '';
  }
  // Validation and accessible error recovery
  const contactFields = [...form.querySelectorAll('[required]')];
  const productChoices = [...form.querySelectorAll('[name="product"]')];
  const productError = document.createElement('p');
  productError.id = 'product-error'; productError.className = 'field-error';
  productError.hidden = true; productError.setAttribute('role','alert');
  productChoices[0].closest('.choices').before(productError);
  const fieldErrors = new Map();
  function markInvalid(input, message) {
    let feedback = fieldErrors.get(input);
    if (!feedback) {
      feedback = document.createElement('p'); feedback.id = input.id + '-error';
      feedback.className = 'field-error'; feedback.setAttribute('role','alert');
      input.closest('label').after(feedback); fieldErrors.set(input,feedback);
    }
    feedback.textContent = message; feedback.hidden = false;
    input.setAttribute('aria-invalid','true');
    const descriptions = new Set((input.getAttribute('aria-describedby') || '').split(' ').filter(Boolean));
    descriptions.add(feedback.id); input.setAttribute('aria-describedby',[...descriptions].join(' '));
  }
  function clearInvalid(input) {
    const feedback = fieldErrors.get(input);
    if (feedback) {
      feedback.hidden = true;
      const descriptions = (input.getAttribute('aria-describedby') || '').split(' ').filter(id => id && id !== feedback.id);
      if (descriptions.length) input.setAttribute('aria-describedby',descriptions.join(' '));
      else input.removeAttribute('aria-describedby');
    }
    input.removeAttribute('aria-invalid');
  }
  function syncProductError() {
    if (!products().length) return;
    productError.hidden = true;
    productChoices.forEach(input => {
      input.removeAttribute('aria-invalid');
      const descriptions = (input.getAttribute('aria-describedby') || '').split(' ').filter(id => id && id !== productError.id);
      if (descriptions.length) input.setAttribute('aria-describedby',descriptions.join(' '));
      else input.removeAttribute('aria-describedby');
    });
  }
  contactFields.forEach(input => input.addEventListener('input', () => {if (input.checkValidity()) clearInvalid(input);}));
  productChoices.forEach(input => input.addEventListener('change',syncProductError));
  function valid() {
    error.hidden = true;
    let firstInvalid;
    contactFields.forEach(input => {
      if (input.checkValidity()) {clearInvalid(input); return;}
      const messages = {'Full name':'Enter your name.','Email':'Enter a valid email address, such as name@example.com.','Phone':'Enter a contact number.'};
      markInvalid(input,messages[input.name] || 'Complete this field.');
      firstInvalid ||= input;
    });
    if (!products().length) {
      productError.textContent = 'Select at least one product to request a quote.'; productError.hidden = false;
      productChoices.forEach(input => {input.setAttribute('aria-invalid','true'); input.setAttribute('aria-describedby',productError.id);});
      firstInvalid ||= productChoices[0];
    } else syncProductError();
    if (firstInvalid) {
      if (root.querySelector('#quote-entry').hidden) showEntry();
      firstInvalid.focus({preventScroll:true}); firstInvalid.scrollIntoView({block:'center'});
      return false;
    }
    return true;
  }
  root.querySelectorAll('[data-product-toggle]').forEach(button => {
    button.onclick = () => {
      const expanded = button.getAttribute('aria-expanded') !== 'true';
      button.setAttribute('aria-expanded', String(expanded));
      const productLabel = {'Roof Trusses':'Roof Truss','Wall Panels':'Wall Panel','EWP/Floors':'EWP/Floor'}[button.dataset.productToggle];
      if (expanded) productDetailsIncluded.add(button.dataset.productToggle);
      button.textContent = (expanded ? 'Hide ' : 'Add ') + productLabel + ' details';
      confirmation.hidden = true;
      draw();
    };
  });
  const detailsToggle = root.querySelector('#toggle-details');
  const additionalDetails = root.querySelector('#additional-details');
  const reviewSection = root.querySelector('#quote-review');
  detailsToggle.onclick = () => {
    detailsExpanded = !detailsExpanded;
    additionalDetails.hidden = !detailsExpanded;
    if (detailsExpanded) projectDetailsIncluded = true;
    additionalDetails.disabled = !projectDetailsIncluded;
    detailsToggle.setAttribute('aria-expanded', String(detailsExpanded));
    detailsToggle.innerHTML = (detailsExpanded ? 'Hide Project &amp; Jobsite Details (optional)' : 'Add More Project &amp; Jobsite Details (optional)') + ' <span aria-hidden="true">' + (detailsExpanded ? '−' : '+') + '</span>';
    confirmation.hidden = true;
    draw();
    if (detailsExpanded) {
      additionalDetails.classList.remove('field-enter');
      void additionalDetails.offsetWidth;
      additionalDetails.classList.add('field-enter');
    } else additionalDetails.classList.remove('field-enter');
  };
  // Review navigation and editing
  const entry = root.querySelector('#quote-entry');
  const pageHeader = root.querySelector('.sheet > header');
  function focusReviewTarget(target) {
    if (!target) return;
    if (!target.matches('input,select,textarea,button')) target.tabIndex = -1;
    target.focus({preventScroll:true}); target.scrollIntoView({block:'center'});
  }
  function showEntry() {
    if (activeReviewEdit) finishReviewEdit(false);
    entry.hidden = false; pageHeader.hidden = false; reviewSection.hidden = true;
    confirmation.hidden = true;
    history.replaceState(null,'',location.pathname + location.search);
  }
  function editReview(target, section) {
    if (activeReviewEdit) {
      activeReviewEdit.editor.querySelector('.inline-edit-status').textContent = 'Save or cancel these changes before editing another section.';
      focusReviewTarget(activeReviewEdit.editor.querySelector('input,select,textarea,button'));
      return;
    }
    const snapshot = [...form.querySelectorAll('input,select,textarea')].map(input => ({input,value:input.value,checked:input.checked,disabled:input.disabled}));
    const nodes = [];
    if (products().includes(target)) {
      if (target === 'Wall Panels') nodes.push(root.querySelector('#wall-scope-options'));
      nodes.push([...root.querySelectorAll('[data-product]')].find(panel => panel.dataset.product === target));
    } else if (target === 'project') nodes.push(additionalDetails);
    else if (target === 'building') nodes.push(root.querySelector('.building-use'));
    else if (target === 'drawings') nodes.push(dropZone,uploadStatus,attachmentList);
    else if (target === 'services') nodes.push(root.querySelector('#quote-services'));
    else nodes.push(root.querySelector('[data-panel="0"] > .grid'));
    const editor = document.createElement('div'); editor.className = 'inline-review-editor';
    const placements = nodes.map(node => {
      const placeholder = document.createComment('inline edit return position');
      node.before(placeholder);
      const state = {node,placeholder,hidden:node.hidden,disabled:node.disabled};
      editor.append(node); node.hidden = false;
      if (node.tagName === 'FIELDSET') node.disabled = false;
      return state;
    });
    const status = document.createElement('p'); status.className = 'inline-edit-status'; status.setAttribute('role','status');
    const actions = document.createElement('div'); actions.className = 'inline-edit-actions';
    const save = document.createElement('button'); save.type = 'button'; save.className = 'primary'; save.textContent = 'Save changes'; save.onclick = saveReviewEdit;
    const cancel = document.createElement('button'); cancel.type = 'button'; cancel.className = 'review-edit'; cancel.textContent = 'Cancel'; cancel.onclick = () => finishReviewEdit(false);
    actions.append(save,cancel); editor.append(status,actions);
    activeReviewEdit = {target,section,editor,placements,snapshot,drawings:[...drawings],title:section.querySelector('h2').textContent};
    section.querySelectorAll('.review-facts,.review-empty,.review-edit').forEach(node => node.hidden = true);
    section.append(editor);
    root.querySelector('#submit-quote').disabled = true;
    focusReviewTarget(editor.querySelector('input:not(:disabled),select:not(:disabled),textarea:not(:disabled),button'));
  }
  function saveReviewEdit() {
    if (!activeReviewEdit) return;
    const invalid = [...activeReviewEdit.editor.querySelectorAll('input,select,textarea')].find(input => !input.matches(':disabled') && !input.checkValidity());
    if (invalid) {
      activeReviewEdit.editor.querySelector('.inline-edit-status').textContent = invalid.validationMessage || 'Check this value before saving.';
      invalid.setAttribute('aria-invalid','true'); focusReviewTarget(invalid); invalid.reportValidity(); return;
    }
    finishReviewEdit(true);
  }
  function finishReviewEdit(save) {
    const edit = activeReviewEdit;
    if (!edit) return;
    if (!save) {
      edit.snapshot.forEach(({input,value,checked,disabled}) => {if (input.type !== 'file') input.value = value; input.checked = checked; input.disabled = disabled; clearInvalid(input);});
      drawings = edit.drawings;
      showDrawings();
    } else {
      if (products().includes(edit.target)) productDetailsIncluded.add(edit.target);
      if (edit.target === 'project') projectDetailsIncluded = true;
      edit.editor.querySelectorAll('input,select,textarea').forEach(clearInvalid);
    }
    edit.placements.forEach(({node,placeholder,hidden,disabled}) => {
      placeholder.replaceWith(node); node.hidden = hidden;
      if (node.tagName === 'FIELDSET') node.disabled = disabled;
    });
    additionalDetails.disabled = !projectDetailsIncluded;
    activeReviewEdit = null;
    root.querySelector('#submit-quote').disabled = false;
    confirmation.hidden = true;
    draw();
    const editButton = [...root.querySelectorAll('.review-group-heading button')].find(button => button.getAttribute('aria-label') === 'Edit ' + edit.title);
    focusReviewTarget(editButton);
  }
  form.addEventListener('keydown', event => {
    if (event.key === 'Escape' && activeReviewEdit && !confirmation.open) {event.preventDefault(); finishReviewEdit(false);}
  });

  root.querySelector('#review-request').onclick = () => {
    if (!valid()) return;
    renderReview(); entry.hidden = true; pageHeader.hidden = true;
    reviewSection.hidden = false; confirmation.hidden = true;
    history.pushState({quoteReview:true},'', '#review');
    focusReviewTarget(reviewSection.querySelector('h1'));
  };
  window.addEventListener('popstate', () => {
    if (location.hash !== '#review' && !reviewSection.hidden) {showEntry(); focusReviewTarget(root.querySelector('#review-request'));}
  });

  form.addEventListener('change', event => {
    confirmation.hidden = true;
    draw();
  });
  form.addEventListener('input', renderReview);
  
  
form.addEventListener('submit', async event => {

  event.preventDefault();

  if (activeReviewEdit) {
    saveReviewEdit();
    return;
  }

  if (!valid()) return;

  if (reviewSection.hidden) {
    root.querySelector('#review-request').click();
    return;
  }


  const submitButton =
    root.querySelector('#submit-quote');

  // Enter can submit the form again even while the button is disabled.
  if (submitButton.disabled) return;

  const originalButtonText =
    submitButton.textContent;


  submitButton.disabled = true;
  submitButton.textContent = 'Sending Request...';

  error.hidden = true;


  try {

    // Collect all normal form fields
    const formData =
      new FormData(form);

    // Preserve quote.html's field name while sending every selected product.
    // PHP would otherwise keep only the last repeated "product" value.
    formData.set('product', formData.getAll('product').join(', '));


    // The custom drawing uploader stores files
    // in the drawings[] array and clears the
    // actual file input, so add them manually.
    drawings.forEach(file => {

      formData.append(
        'files[]',
        file,
        file.name
      );

    });


    // Upload contact1.php as contact.php on the server.
    const response =
      await fetch(
        'contact.php',
        {
          method: 'POST',
          body: formData
        }
      );


    // Read raw response first so server errors
    // don't get hidden behind a JSON parse error.
    const responseText =
      await response.text();


    let result;


    try {

      result =
        JSON.parse(responseText);

    } catch (parseError) {

      console.error(
        'Invalid PHP response:',
        responseText
      );

      throw new Error(
        'The server could not process the request.'
      );

    }


    if (
      !response.ok ||
      result?.success !== true
    ) {

      throw new Error(
        result?.message ||
        'Unable to send your quote request.'
      );

    }


    // Successful submission
    renderReview();

    confirmation.hidden = false;

    if (!confirmation.open) {
      confirmation.showModal();
    }


  } catch (submissionError) {

    console.error(
      'Quote submission error:',
      submissionError
    );


    error.textContent =
      submissionError.message ||
      'We could not send your quote request. Please try again.';

    error.hidden = false;

    error.scrollIntoView({
      behavior: 'smooth',
      block: 'center'
    });


  } finally {

    submitButton.disabled = false;
    submitButton.textContent =
      originalButtonText;

  }

});


root.querySelector('#close-confirmation').onclick = () => {
  confirmation.close();
};


confirmation.addEventListener('close', () => {
  window.location.assign('index.html');
});
  
  
  
  // Diagram help
  root.querySelectorAll('.diagram-thumbnail').forEach(button => {
    button.onclick = () => {
      const figure = document.getElementById(button.getAttribute('aria-controls'));
      const expanded = button.getAttribute('aria-expanded') !== 'true';
      button.setAttribute('aria-expanded', String(expanded));
      button.querySelector('span').textContent = expanded ? 'Collapse diagram' : 'Expand diagram';
      figure.hidden = !expanded;
    };
  });
  // Allow Tab to visit every building-use choice, while preserving native
  // radio selection and arrow-key behavior.
  const buildingUseChoices = [...root.querySelectorAll('.building-use input[type="radio"]')];
  buildingUseChoices.forEach((choice, index) => {
    choice.addEventListener('keydown', event => {
      if (event.key !== 'Tab' || event.ctrlKey || event.altKey || event.metaKey) return;
      const nextChoice = buildingUseChoices[index + (event.shiftKey ? -1 : 1)];
      if (nextChoice) {
        event.preventDefault();
        nextChoice.focus();
      }
    });
  });
  // Mobile navigation
  const hamburger = document.getElementById('hamburger-button');
  const menu = document.getElementById('mobile-menu');
  hamburger.setAttribute('aria-controls',menu.id);
  function setMenuOpen(open, returnFocus = false) {
    menu.hidden = !open; menu.inert = !open;
    menu.classList.toggle('open',open); hamburger.classList.toggle('active',open);
    hamburger.setAttribute('aria-expanded',String(open));
    hamburger.setAttribute('aria-label',open ? 'Close navigation menu' : 'Open navigation menu');
    if (returnFocus) hamburger.focus();
  }
  setMenuOpen(false);
  hamburger.onclick = () => setMenuOpen(menu.hidden);
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && !menu.hidden) {event.preventDefault(); setMenuOpen(false,true);}
  });
  menu.addEventListener('click', event => {if (event.target.closest('a')) setMenuOpen(false);});
  menu.addEventListener('focusout', event => {
    if (event.relatedTarget && !menu.contains(event.relatedTarget) && event.relatedTarget !== hamburger) setMenuOpen(false);
  });
  window.addEventListener('resize', () => {
    if (getComputedStyle(hamburger).display === 'none') {
      const focusWasInMenu = menu.contains(document.activeElement);
      setMenuOpen(false);
      if (focusWasInMenu) document.querySelector('.logo-button').focus();
    }
  });
  draw();
})();
