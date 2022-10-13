import { test, expect } from '@playwright/test';

/**
 * Local deps.
 */
 import { pageUtils } from '../../utils/page.utils';

test.describe('Rocket License', () => {
    let page;
    
    test.beforeAll(async ({ browser }) => {
        const context = await browser.newContext();
        page = await context.newPage();

        // Goto WPR settings.
        const page_utils = new pageUtils(page);
        await page_utils.goto_wpr();
    });

    test.afterAll(async ({ browser }) => {
        browser.close;
    });

    test( 'should validate license if customer key is correct', async () => {

        const validate_btn = 'text=Validate License';

        const locator = {
            'validate': page.locator( validate_btn ),
            'has_license': page.locator( 'span:has-text("License")' )
        };

        if (await locator.validate.isVisible()) {
            await page.waitForSelector( validate_btn, { timeout: 5000 } )
            await expect( locator.validate ).toBeVisible();
            // Validate license
            await locator.validate.click();

            // Expect validation to be successful
            await expect(locator.has_license).toBeVisible();
            return;
        }

        // Expect validation to be successful
        await expect(locator.has_license).toBeVisible();
    });

    test( 'Should display preload trigger message on first activation', async () => {
        await expect(page.locator('#rocket-notice-preload-processing')).toContainText('The preload service is now active');
    });
});