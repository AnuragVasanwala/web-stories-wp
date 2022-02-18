/*
 * Copyright 2021 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * External dependencies
 */
import { within } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { Fixture } from '../../../karma';

describe('Publish Story Modal', () => {
  let fixture;
  let publishModal;

  async function openPublishModal() {
    const { publish } = fixture.editor.titleBar;
    await fixture.events.click(publish);
  }

  beforeEach(async () => {
    fixture = new Fixture();
    fixture.setFlags({ enableUpdatedPublishStoryModal: true });
    await fixture.render();

    await openPublishModal();
    publishModal = await fixture.screen.findByRole('dialog', {
      name: /^Story details$/,
    });
  });

  afterEach(() => {
    fixture.restore();
  });

  function getPublishModalElement(role, name) {
    const { findByRole } = within(publishModal);

    return findByRole(role, {
      name,
    });
  }

  describe('Basic structure', () => {
    it('should have no aXe accessibility violations', async () => {
      await expectAsync(publishModal).toHaveNoViolations();
    });
  });

  describe('Functionality', () => {
    it('should only allow publish of a Story when both title and description are not empty', async () => {
      let publishButton = await getPublishModalElement('button', 'Publish');
      expect(typeof publishButton.getAttribute('disabled')).toBe('string');

      const storyTitle = await getPublishModalElement('textbox', 'Story Title');
      await fixture.events.focus(storyTitle);
      await fixture.events.keyboard.type('my test story');
      await fixture.events.keyboard.press('tab');

      const storyDescription = await getPublishModalElement(
        'textbox',
        'Story Description'
      );
      await fixture.events.focus(storyDescription);
      await fixture.events.keyboard.type('my test description for my story');
      await fixture.events.keyboard.press('tab');

      publishButton = await getPublishModalElement('button', 'Publish');
      expect(publishButton.getAttribute('disabled')).toBeNull();
    });

    it('should close publish modal and open the checklist when checklist button is clicked', async () => {
      const checklistButton = await getPublishModalElement(
        'button',
        'Checklist'
      );
      await fixture.events.click(checklistButton);

      const updatedPublishModal = await fixture.screen.queryByRole('dialog', {
        name: /^Story details$/,
      });

      expect(updatedPublishModal).toBeNull();
      expect(
        fixture.editor.checklist.issues.getAttribute('data-isexpanded')
      ).toBe('true');
    });

    it('should not update story permalink when title is updated if permalink already exists', async () => {
      // Give story initial title
      const storyTitle = await getPublishModalElement('textbox', 'Story Title');
      await fixture.events.focus(storyTitle);
      await fixture.events.keyboard.type('Stews for long journeys');
      await fixture.events.keyboard.press('tab');

      const storySlug = await getPublishModalElement('textbox', 'URL slug');
      // that initial title should give us an initial slug
      expect(storySlug.getAttribute('value')).toBe('stews-for-long-journeys');

      await fixture.events.focus(storySlug);
      await fixture.events.keyboard.type(
        "bilbo's favorite 30 minute rabbit stew"
      );
      await fixture.events.keyboard.press('tab');
      // now we've updated the slug independent of title
      expect(storySlug.getAttribute('value')).toBe(
        'bilbos-favorite-30-minute-rabbit-stew'
      );

      // Update the title
      await fixture.events.focus(storyTitle);
      await fixture.events.keyboard.type('Travel Stews With Bilbo');
      await fixture.events.keyboard.press('tab');

      // slug should remain as it was
      expect(storySlug.getAttribute('value')).toBe(
        'bilbos-favorite-30-minute-rabbit-stew'
      );
    });

    it('should toggle from auto page advancement by default to manual', async () => {
      const manualInput = await getPublishModalElement('radio', 'Manual');
      const autoInput = await getPublishModalElement('radio', 'Auto');

      await fixture.events.click(manualInput.closest('label'));

      expect(typeof autoInput.getAttribute('checked')).toBe('string');
      expect(manualInput.getAttribute('checked')).toBeNull();
    });
  });

  describe('Keyboard navigation', () => {
    it('should navigate modal by keyboard', async () => {
      expect(publishModal).toHaveFocus();

      await fixture.events.keyboard.press('tab');

      const closeButton = await getPublishModalElement('button', 'Close');
      expect(closeButton).toHaveFocus();

      await fixture.events.keyboard.press('tab');

      const storyTitle = await getPublishModalElement('textbox', 'Story Title');
      expect(storyTitle).toHaveFocus();

      await fixture.events.keyboard.press('tab');

      const storyDescription = await getPublishModalElement(
        'textbox',
        'Story Description'
      );
      expect(storyDescription).toHaveFocus();
    });
  });
});