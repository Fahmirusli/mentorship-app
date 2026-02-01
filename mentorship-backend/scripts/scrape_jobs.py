import json
import sys
import time
import os
import random
import logging

# Suppress logs
os.environ['WDM_LOG_LEVEL'] = '0'
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3'

from bs4 import BeautifulSoup
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from webdriver_manager.chrome import ChromeDriverManager

# Configure logging to stderr so it doesn't corrupt stdout JSON
logging.basicConfig(stream=sys.stderr, level=logging.INFO, format='[%(levelname)s] %(message)s')

def get_driver():
    options = Options()
    # options.add_argument('--headless=new') # Headless mode often triggers anti-bot. Use with caution.
    # We will try headless=new but with more evasion techniques.
    options.add_argument('--headless=new')
    options.add_argument('--disable-gpu')
    options.add_argument('--no-sandbox')
    options.add_argument('--disable-dev-shm-usage')
    options.add_argument(f'--window-size={random.randint(1024, 1920)},{random.randint(768, 1080)}')
    options.add_argument('--disable-blink-features=AutomationControlled')
    options.add_argument('--disable-infobars')
    
    # Randomize User-Agent
    user_agents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/121.0'
    ]
    options.add_argument(f'user-agent={random.choice(user_agents)}')
    
    options.add_experimental_option("excludeSwitches", ["enable-automation"])
    options.add_experimental_option('useAutomationExtension', False)
    
    # Initialize driver
    service = Service(ChromeDriverManager().install())
    driver = webdriver.Chrome(service=service, options=options)
    
    # Evasion: Execute CDP commands to modify navigator properties
    driver.execute_cdp_cmd("Page.addScriptToEvaluateOnNewDocument", {
        "source": """
            Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
            Object.defineProperty(navigator, 'plugins', { get: () => [1, 2, 3] });
            Object.defineProperty(navigator, 'languages', { get: () => ['en-US', 'en'] });
        """
    })
    
    return driver

def random_sleep(min_sec=2, max_sec=5):
    time.sleep(random.uniform(min_sec, max_sec))

def scrape_jobstreet(driver, keyword):
    logging.info(f"Scraping JobStreet for '{keyword}'...")
    jobs = []
    try:
        url = f"https://www.jobstreet.com.my/jobs?keywords={keyword}"
        driver.get(url)
        random_sleep()
        
        try:
            WebDriverWait(driver, 10).until(
                EC.presence_of_element_located((By.TAG_NAME, 'article'))
            )
        except:
            logging.warning("JobStreet: No articles found (timeout).")

        soup = BeautifulSoup(driver.page_source, 'html.parser')
        
        articles = soup.find_all('article')
        if not articles:
            articles = soup.select('[data-automation="jobListing"]')

        logging.info(f"JobStreet: Found {len(articles)} potential jobs.")

        for article in articles[:5]:
            try:
                title_elem = article.find('a', attrs={'data-automation': 'jobTitle'}) or article.find('h1').find('a')
                company_elem = article.find('a', attrs={'data-automation': 'jobCompany'})
                location_elem = article.find('a', attrs={'data-automation': 'jobLocation'})
                
                # Salary Extraction
                salary = 'Not specified'
                # Look for typical salary containers or text containing 'MYR'
                salary_elems = article.select('span')
                for span in salary_elems:
                    text = span.get_text(strip=True)
                    if 'MYR' in text or ('RM' in text and any(char.isdigit() for char in text)):
                        salary = text
                        break

                if title_elem:
                    jobs.append({
                        'title': title_elem.text.strip(),
                        'company': company_elem.text.strip() if company_elem else 'Confidential',
                        'location': location_elem.text.strip() if location_elem else 'Malaysia',
                        'source': 'jobstreet',
                        'external_url': "https://www.jobstreet.com.my" + title_elem['href'].split('?')[0],
                        'description': "See full details at JobStreet.",
                        'salary': salary,
                        'requirements': []
                    })
            except Exception as e:
                logging.warning(f"JobStreet info extraction error: {e}")
                continue
    except Exception as e:
        logging.error(f"JobStreet Error: {e}")
        
    return jobs

def scrape_hiredly(driver, keyword):
    logging.info(f"Scraping Hiredly for '{keyword}'...")
    jobs = []
    try:
        url = f"https://my.hiredly.com/jobs?keywords={keyword}"
        driver.get(url)
        random_sleep(3, 6)
        
        soup = BeautifulSoup(driver.page_source, 'html.parser')
        cards = soup.select('a[href^="/jobs/"]')
        
        logging.info(f"Hiredly: Found {len(cards)} potential job cards.")
        
        unique_ids = set()
        for card in cards:
            if len(jobs) >= 5: break
            
            href = card.get('href')
            if href in unique_ids: continue
            unique_ids.add(href)
            
            title_tag = card.find('h2') or card.find('h3')
            
            company = "Hiredly Listed Company"
            salary = "Not specified"
            
            p_tags = card.find_all('p')
            for p in p_tags:
                text = p.get_text(strip=True)
                if 'RM' in text and any(char.isdigit() for char in text):
                    salary = text
                elif len(text) > 3 and "Company" in company: # heuristic for company name if generic
                    company = text 
            
            # Often the first p is company, second is location/salary
            if p_tags and len(p_tags) > 0:
                 company = p_tags[0].text.strip()

            if title_tag:
                jobs.append({
                    'title': title_tag.text.strip(),
                    'company': company, 
                    'location': 'Malaysia',
                    'source': 'hiredly',
                    'external_url': "https://my.hiredly.com" + href,
                    'description': "Check Hiredly for details.",
                    'salary': salary,
                    'requirements': []
                })
    except Exception as e:
        logging.error(f"Hiredly Error: {e}")
        
    return jobs

def scrape_linkedin(driver, keyword):
    logging.info(f"Scraping LinkedIn for '{keyword}'...")
    jobs = []
    try:
        url = f"https://www.linkedin.com/jobs/search?keywords={keyword}&location=Malaysia&position=1&pageNum=0"
        driver.get(url)
        random_sleep(3, 7)
        
        soup = BeautifulSoup(driver.page_source, 'html.parser')
        job_cards = soup.find_all('li')
        
        logging.info(f"LinkedIn: Found {len(job_cards)} potential items.")
        
        for card in job_cards:
            if len(jobs) >= 5: break
            try:
                title = card.find(class_='base-search-card__title')
                company = card.find(class_='base-search-card__subtitle')
                link = card.find('a', class_='base-card__full-link')
                location = card.find(class_='job-search-card__location')
                
                # Salary
                salary_tag = card.find(class_='job-search-card__salary-info')
                salary = salary_tag.get_text(strip=True) if salary_tag else 'Not specified'
                
                if title and link:
                    jobs.append({
                        'title': title.text.strip(),
                        'company': company.text.strip() if company else 'LinkedIn',
                        'location': location.text.strip() if location else 'Malaysia',
                        'source': 'linkedin',
                        'external_url': link['href'].split('?')[0],
                        'description': 'Apply on LinkedIn',
                        'salary': salary,
                        'requirements': []
                    })
            except:
                continue
    except Exception as e:
        logging.error(f"LinkedIn Error: {e}")
        
    return jobs

def scrape_indeed(driver, keyword):
    logging.info(f"Scraping Indeed for '{keyword}'...")
    jobs = []
    try:
        url = f"https://malaysia.indeed.com/jobs?q={keyword}"
        driver.get(url)
        random_sleep(4, 8)
        
        soup = BeautifulSoup(driver.page_source, 'html.parser')
        
        job_cards = soup.select('.job_seen_beacon') or soup.select('td.resultContent') or soup.select('.jobsearch-SerpJobCard')
            
        logging.info(f"Indeed: Found {len(job_cards)} potential jobs.")
        
        for card in job_cards[:5]:
            try:
                title_elem = card.select_one('h2.jobTitle span[title]') or card.select_one('.jobTitle span') or card.select_one('h2.jobTitle')
                company_elem = card.select_one('[data-testid="company-name"]') or card.select_one('.companyName')
                location_elem = card.select_one('[data-testid="text-location"]') or card.select_one('.companyLocation')
                link_elem = card.select_one('a.jcs-JobTitle') or card.select_one('h2.jobTitle a')
                
                # Salary
                salary = 'Not specified'
                salary_elem = card.select_one('.salary-snippet') or card.select_one('[data-testid="attribute_snippet_testid"]')
                if salary_elem:
                    salary = salary_elem.get_text(strip=True)

                if title_elem:
                    title = title_elem.text.strip()
                    company = company_elem.text.strip() if company_elem else "Indeed Company"
                    location = location_elem.text.strip() if location_elem else "Malaysia"
                    
                    external_url = "https://malaysia.indeed.com"
                    if link_elem and link_elem.get('href'):
                         href = link_elem.get('href')
                         if href.startswith('/'):
                            external_url += href
                         else:
                            external_url = href
                    
                    jobs.append({
                        'title': title,
                        'company': company,
                        'location': location,
                        'source': 'indeed',
                        'external_url': external_url,
                        'description': "See full details at Indeed.",
                        'salary': salary,
                        'requirements': []
                    })
            except Exception as e:
                logging.warning(f"Indeed Card Error: {e}")
                continue
                
    except Exception as e:
        logging.error(f"Indeed Error: {e}")
        
    return jobs

import argparse

def main():
    parser = argparse.ArgumentParser(description='Scrape jobs with a keyword.')
    parser.add_argument('--keyword', type=str, default='Software Engineer', help='Job keyword to search for')
    args = parser.parse_args()
    
    keyword = args.keyword
    
    driver = None
    all_jobs = []
    try:
        driver = get_driver()
        
        # Run sequentially
        all_jobs.extend(scrape_jobstreet(driver, keyword))
        all_jobs.extend(scrape_hiredly(driver, keyword))
        all_jobs.extend(scrape_linkedin(driver, keyword))
        all_jobs.extend(scrape_indeed(driver, keyword))
        
    except Exception as e:
        logging.critical(f"Global Scraper Error: {str(e)}")
    finally:
        if driver:
            driver.quit()
            
    # Output JSON to file
    output_path = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), 'storage', 'app', 'scraped_jobs.json')
    
    try:
        # Ensure directory exists
        os.makedirs(os.path.dirname(output_path), exist_ok=True)
        
        with open(output_path, 'w', encoding='utf-8') as f:
            json.dump(all_jobs, f, ensure_ascii=False, indent=2)
            
        logging.info(f"Saved {len(all_jobs)} jobs to {output_path}")
        # Print valid JSON to stdout as well if needed by PHP, but PHP reads file.
        print(json.dumps(all_jobs)) 
        
    except Exception as e:
        logging.error(f"Failed to save output: {e}")

if __name__ == "__main__":
    main()
