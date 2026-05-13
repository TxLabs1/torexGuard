from setuptools import setup, find_packages

setup(
    name='torexguard',
    version='1.0.0',
    description='Torex Guard Python SDK — Runtime security & malware detection',
    author='Torex Labs',
    author_email='dev@torexlabs.cloud',
    url='https://torexlabs.cloud',
    packages=find_packages(),
    install_requires=[
        'requests>=2.28.0',
    ],
    extras_require={
        'django': ['django>=3.2'],
        'fastapi': ['starlette>=0.20.0'],
    },
    python_requires='>=3.9',
    classifiers=[
        'Development Status :: 4 - Beta',
        'Intended Audience :: Developers',
        'Topic :: Security',
        'Programming Language :: Python :: 3',
        'License :: OSI Approved :: MIT License',
    ],
)
